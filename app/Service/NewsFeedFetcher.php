<?php
declare(strict_types=1);

namespace App\Service;

use PDO;

class NewsFeedFetcher
{
    private ?PDO $pdo;

    public function __construct(?PDO $pdo = null)
    {
        $this->pdo = $pdo;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function fetch(): array
    {
        $sources = $this->readSources();
        $items = [];

        foreach ($sources as $source) {
            if (!($source['enabled'] ?? false)) {
                continue;
            }

            $sourceItems = $this->fetchSource($source);
            $items = array_merge($items, $sourceItems);
        }

        return $items;
    }

    /**
     * @param array<int, array<string, mixed>> $items
     */
    public function writeItems(string $path, array $items): void
    {
        $payload = json_encode(
            $items,
            JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES
        );

        if ($payload === false) {
            throw new \RuntimeException('Failed to encode items as JSON.');
        }

        if (file_put_contents($path, $payload . PHP_EOL) === false) {
            throw new \RuntimeException('Failed to write items to ' . $path);
        }
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function readSources(): array
    {
        if ($this->pdo === null) {
            throw new \RuntimeException('Database connection is required to read sources.');
        }

        return $this->readSourcesFromDb();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function readSourcesFromDb(): array
    {
        $stmt = $this->pdo->query(
            'SELECT id, name, type, url, country, language, default_image_path, enabled FROM news_sources'
        );
        $rows = $stmt === false ? [] : $stmt->fetchAll(PDO::FETCH_ASSOC);
        if (!is_array($rows)) {
            return [];
        }

        $sources = [];
        foreach ($rows as $row) {
            $sources[] = [
                'id' => (string) ($row['id'] ?? ''),
                'name' => (string) ($row['name'] ?? ''),
                'type' => (string) ($row['type'] ?? ''),
                'url' => (string) ($row['url'] ?? ''),
                'country' => $row['country'] ?? null,
                'language' => $row['language'] ?? null,
                'default_image_path' => $row['default_image_path'] ?? null,
                'enabled' => !empty($row['enabled']),
            ];
        }

        return $sources;
    }

    /**
     * @param array<string, mixed> $source
     * @return array<int, array<string, mixed>>
     */
    private function fetchSource(array $source): array
    {
        $url = (string) ($source['url'] ?? '');
        $sourceId = (string) ($source['id'] ?? '');
        if ($url === '' || $sourceId === '') {
            return [];
        }

        $content = $this->fetchUrl($url);
        if ($content === null) {
            return [];
        }

        $xml = $this->parseXml($content);
        if ($xml === null) {
            return [];
        }

        $items = [];
        $rssItems = $xml->channel->item ?? [];
        if (count($rssItems) === 0) {
            $rssItems = $xml->item ?? [];
        }

        if (count($rssItems) === 0) {
            $rssItems = $xml->entry ?? [];
        }

        foreach ($rssItems as $item) {
            $normalized = $this->normalizeItem($item, $sourceId);
            if ($normalized !== null) {
                $items[] = $normalized;
            }
        }

        return $items;
    }

    private function fetchUrl(string $url): ?string
    {
        $context = stream_context_create([
            'http' => [
                'timeout' => 10,
                'user_agent' => 'NewsAggregator/1.0',
            ],
            'https' => [
                'timeout' => 10,
                'user_agent' => 'NewsAggregator/1.0',
            ],
        ]);

        $content = @file_get_contents($url, false, $context);
        if ($content === false) {
            fwrite(STDERR, 'Failed to fetch ' . $url . PHP_EOL);
            return null;
        }

        return $content;
    }

    private function parseXml(string $content): ?\SimpleXMLElement
    {
        libxml_use_internal_errors(true);
        $xml = simplexml_load_string($content, 'SimpleXMLElement', LIBXML_NOCDATA);
        if ($xml === false) {
            libxml_clear_errors();
            fwrite(STDERR, 'Failed to parse XML feed.' . PHP_EOL);
            return null;
        }

        return $xml;
    }

    /**
     * @param \SimpleXMLElement $item
     * @return array<string, mixed>|null
     */
    private function normalizeItem(\SimpleXMLElement $item, string $sourceId): ?array
    {
        $title = $this->cleanText((string) ($item->title ?? ''));
        $link = $this->extractLink($item);
        $guid = $this->cleanText((string) ($item->guid ?? ''));
        $description = (string) ($item->description ?? '');
        $summary = $this->cleanText($description);
        $pubDate = $this->cleanText((string) ($item->pubDate ?? ''));
        $author = $this->cleanText((string) ($item->author ?? ''));
        $categories = $this->extractCategories($item);
        $category = $categories[0] ?? $this->cleanText((string) ($item->category ?? ''));
        $newsId = $this->cleanText((string) ($item->newsid ?? ''));
        $imageUrl = $this->extractImageUrl($item, $description);
        $imageUrl = $this->normalizeImageUrl($sourceId, $imageUrl);

        if ($title === '' && $link === '') {
            return null;
        }

        $idSeed = $guid !== '' ? $guid : ($link !== '' ? $link : $title);
        $id = sha1($sourceId . '|' . $idSeed);

        $normalized = [
            'id' => $id,
            'source_id' => $sourceId,
            'title' => $title,
            'link' => $link,
            'summary' => $summary !== '' ? $summary : null,
            'published_at' => $this->normalizeDate($pubDate),
            'author' => $author !== '' ? $author : null,
            'category' => $category !== '' ? $category : null,
            'categories' => $categories,
            'image_url' => $imageUrl,
            'raw_guid' => $guid !== '' ? $guid : null,
            'raw_extra' => $newsId !== '' ? ['news_id' => $newsId] : [],
        ];

        return $normalized;
    }

    private function extractLink(\SimpleXMLElement $item): string
    {
        $link = $this->cleanText((string) ($item->link ?? ''));
        if ($link !== '') {
            return $link;
        }

        $atom = $item->children('http://www.w3.org/2005/Atom');
        if (isset($atom->link)) {
            foreach ($atom->link as $linkNode) {
                $href = $this->cleanText((string) ($linkNode['href'] ?? ''));
                if ($href !== '') {
                    return $href;
                }
            }
        }

        return '';
    }

    private function extractImageUrl(\SimpleXMLElement $item, string $description): ?string
    {
        $enclosureUrl = $this->cleanText((string) ($item->enclosure['url'] ?? ''));
        $enclosureUrl = $this->stripQueryParams($enclosureUrl);
        if ($enclosureUrl !== '') {
            return $enclosureUrl;
        }

        if (preg_match('/<img[^>]+src=["\\\']([^"\\\']+)["\\\']/i', $description, $matches)) {
            $imageUrl = html_entity_decode($matches[1], ENT_QUOTES | ENT_HTML5, 'UTF-8');
            $imageUrl = trim($imageUrl);
            $imageUrl = $this->stripQueryParams($imageUrl);
            if ($imageUrl !== '') {
                return $imageUrl;
            }
        }

        return null;
    }

    private function normalizeImageUrl(string $sourceId, ?string $imageUrl): ?string
    {
        if ($imageUrl === null || $imageUrl === '') {
            return $imageUrl;
        }

        $normalized = $imageUrl;

        if ($sourceId === 'correio_manha') {
            $normalized = str_replace('/img_100x100', '/img_932x621', $normalized);
        }

        if ($sourceId === 'sabado') {
            $normalized = str_replace('/img_182x101', '/img_980x653', $normalized);
        }

        return $normalized;
    }

    /**
     * @param \SimpleXMLElement $item
     * @return array<int, string>
     */
    private function extractCategories(\SimpleXMLElement $item): array
    {
        $categories = [];
        if (isset($item->category)) {
            foreach ($item->category as $categoryNode) {
                $value = $this->cleanText((string) $categoryNode);
                if ($value !== '') {
                    $categories[] = $value;
                }
            }
        }

        $categories = array_values(array_unique($categories));
        return $categories;
    }

    private function normalizeDate(string $value): ?string
    {
        $timestamp = strtotime($value);
        if ($timestamp === false) {
            return null;
        }

        return gmdate('c', $timestamp);
    }

    private function stripQueryParams(string $url): string
    {
        if ($url === '') {
            return '';
        }

        $parts = parse_url($url);
        if ($parts === false) {
            return $url;
        }

        $scheme = $parts['scheme'] ?? '';
        $host = $parts['host'] ?? '';
        if ($scheme === '' || $host === '') {
            return $url;
        }

        $normalized = $scheme . '://' . $host;
        if (!empty($parts['port'])) {
            $normalized .= ':' . $parts['port'];
        }
        $normalized .= $parts['path'] ?? '';

        return $normalized;
    }

    private function cleanText(string $value): string
    {
        $value = html_entity_decode($value, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        if (strpos($value, '<![CDATA[') !== false) {
            $value = preg_replace('/<!\\[CDATA\\[(.*?)\\]\\]>/s', '$1', $value);
        }
        $value = strip_tags($value);
        $value = preg_replace('/\\s+/', ' ', $value);

        return trim($value ?? '');
    }
}
