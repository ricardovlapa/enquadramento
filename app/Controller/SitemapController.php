<?php

namespace App\Controller;

use App\Model\NewsRepository;
use App\Model\OpinionRepository;

class SitemapController extends BaseController
{
    private NewsRepository $news;
    private OpinionRepository $opinions;

    public function __construct(array $site, NewsRepository $news, OpinionRepository $opinions)
    {
        parent::__construct($site);
        $this->news = $news;
        $this->opinions = $opinions;
    }

    public function show(): void
    {
        header('Content-Type: application/xml; charset=UTF-8');

        $baseUrl = $this->getBaseUrl();
        $urls = $this->buildUrls($baseUrl);

        $esc = fn(string $value): string => htmlspecialchars($value, ENT_XML1, 'UTF-8');

        echo "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n";
        echo "<urlset xmlns=\"http://www.sitemaps.org/schemas/sitemap/0.9\">\n";
        foreach ($urls as $url) {
            echo "  <url>\n";
            echo "    <loc>" . $esc($url['loc']) . "</loc>\n";
            if (!empty($url['lastmod'])) {
                echo "    <lastmod>" . $esc($url['lastmod']) . "</lastmod>\n";
            }
            if (!empty($url['priority'])) {
                echo "    <priority>" . $esc($url['priority']) . "</priority>\n";
            }
            echo "  </url>\n";
        }
        echo "</urlset>\n";
    }

    private function buildUrls(string $baseUrl): array
    {
        $urls = [];
        $seen = [];

        $addUrl = function (string $path, string $priority = '', ?string $lastmod = null) use (&$urls, &$seen, $baseUrl): void {
            $loc = $path;
            if (!preg_match('~^https?://~i', $loc)) {
                $loc = $path === '/' ? ($baseUrl . '/') : ($baseUrl . $path);
            }
            if ($loc === $baseUrl) {
                $loc .= '/';
            }
            if (isset($seen[$loc])) {
                return;
            }
            $seen[$loc] = true;

            $entry = ['loc' => $loc];
            if ($lastmod !== null && $lastmod !== '') {
                $entry['lastmod'] = $lastmod;
            }
            if ($priority !== '') {
                $entry['priority'] = $priority;
            }
            $urls[] = $entry;
        };

        $newsItems = $this->news->all();
        $latestNewsTs = $this->maxTimestamp($newsItems, 'published_at');
        $latestNewsLastmod = $this->formatDate($latestNewsTs);

        $addUrl('/', '1.0', $latestNewsLastmod);
        $addUrl('/todas-as-noticias', '0.8', $latestNewsLastmod);
        $addUrl('/opiniao-enquadramento', '0.7', $this->formatDate($this->maxTimestamp($this->opinions->allArticles(), 'published_at')));

        $addUrl('/sobre', '0.4');
        $addUrl('/contactos', '0.4');
        $addUrl('/nota-editorial', '0.3');
        $addUrl('/termos-de-utilizacao', '0.3');
        $addUrl('/politica-de-privacidade', '0.3');

        $categoryLastmod = $this->latestNewsByCategory($newsItems);
        foreach ($this->news->categories() as $category) {
            if (!is_array($category)) {
                continue;
            }
            $slug = trim((string) ($category['slug'] ?? ''));
            if ($slug === '' || $slug === 'opiniao-enquadramento') {
                continue;
            }
            $count = (int) ($category['count'] ?? 0);
            if ($count <= 0) {
                continue;
            }
            $path = $slug === 'opiniao-enquadramento'
                ? '/opiniao-enquadramento'
                : '/noticias/categoria/' . $slug;
            $lastmod = $this->formatDate($categoryLastmod[$slug] ?? 0);
            $addUrl($path, '0.6', $lastmod);
        }

        foreach ($this->opinions->allArticles() as $article) {
            if (!is_array($article)) {
                continue;
            }
            $slug = trim((string) ($article['slug'] ?? ''));
            if ($slug === '') {
                continue;
            }
            $lastmod = $this->formatDate($this->parseTimestamp((string) ($article['published_at'] ?? '')));
            $addUrl('/opiniao-enquadramento/' . $slug, '0.5', $lastmod);
        }

        return $urls;
    }

    private function latestNewsByCategory(array $items): array
    {
        $latest = [];
        foreach ($items as $item) {
            if (!is_array($item)) {
                continue;
            }
            $slug = trim((string) ($item['category_slug'] ?? ''));
            if ($slug === '') {
                continue;
            }
            $ts = $this->parseTimestamp((string) ($item['published_at'] ?? ''));
            if ($ts <= 0) {
                continue;
            }
            if (!isset($latest[$slug]) || $ts > $latest[$slug]) {
                $latest[$slug] = $ts;
            }
        }

        return $latest;
    }

    private function maxTimestamp(array $items, string $key): int
    {
        $max = 0;
        foreach ($items as $item) {
            if (!is_array($item)) {
                continue;
            }
            $ts = $this->parseTimestamp((string) ($item[$key] ?? ''));
            if ($ts > $max) {
                $max = $ts;
            }
        }

        return $max;
    }

    private function parseTimestamp(string $value): int
    {
        $value = trim($value);
        if ($value === '') {
            return 0;
        }
        $timestamp = strtotime($value);
        return $timestamp === false ? 0 : $timestamp;
    }

    private function formatDate(int $timestamp): ?string
    {
        if ($timestamp <= 0) {
            return null;
        }
        return gmdate('Y-m-d', $timestamp);
    }
}
