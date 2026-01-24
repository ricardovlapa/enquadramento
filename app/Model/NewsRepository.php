<?php

namespace App\Model;

use PDO;

class NewsRepository
{
    private string $categoryTrainingFile;
    private ?array $sourcesIndex = null;
    private array $fixedCategories;
    private ?array $normalizedCategories = null;
    private ?array $categoryTrainingIndex = null;
    private ?PDO $pdo;

    public function __construct(
        array $fixedCategories = [],
        string $categoryTrainingFile = '',
        ?PDO $pdo = null
    )
    {
        $this->fixedCategories = $fixedCategories;
        $this->categoryTrainingFile = $categoryTrainingFile;
        $this->pdo = $pdo;
    }

    public function all(): array
    {
        $items = $this->attachDerived($this->loadItems());

        usort($items, function (array $a, array $b): int {
            return ($b['_published_ts'] ?? 0) <=> ($a['_published_ts'] ?? 0);
        });

        foreach ($items as &$item) {
            unset($item['_published_ts']);
        }
        unset($item);

        return $items;
    }

    public function categories(): array
    {
        $fixed = $this->getNormalizedCategories();
        $counts = [];
        $items = $this->all();
        foreach ($items as $item) {
            $slug = (string) ($item['category_slug'] ?? '');
            if ($slug === '') {
                continue;
            }
            $counts[$slug] = ($counts[$slug] ?? 0) + 1;
        }

        if ($fixed === []) {
            $dynamic = [];
            foreach ($items as $item) {
                $slug = (string) ($item['category_slug'] ?? '');
                $label = (string) ($item['category_label'] ?? '');
                if ($slug === '' || $label === '') {
                    continue;
                }
                if (!isset($dynamic[$slug])) {
                    $dynamic[$slug] = [
                        'slug' => $slug,
                        'label' => $label,
                        'count' => 0,
                    ];
                }
                $dynamic[$slug]['count']++;
            }

            $categories = array_values($dynamic);
            usort($categories, function (array $a, array $b): int {
                return strcasecmp($a['label'] ?? '', $b['label'] ?? '');
            });

            return $categories;
        }

        $fixedIndex = [];
        foreach ($fixed as $category) {
            $slug = (string) ($category['slug'] ?? '');
            if ($slug !== '') {
                $fixedIndex[$slug] = true;
            }
        }

        $dynamic = [];
        foreach ($items as $item) {
            $slug = (string) ($item['category_slug'] ?? '');
            $label = (string) ($item['category_label'] ?? '');
            if ($slug === '' || $label === '' || isset($fixedIndex[$slug])) {
                continue;
            }
            if (!isset($dynamic[$slug])) {
                $dynamic[$slug] = [
                    'slug' => $slug,
                    'label' => $label,
                    'count' => 0,
                ];
            }
            $dynamic[$slug]['count']++;
        }

        foreach ($fixed as &$category) {
            $category['count'] = $counts[$category['slug']] ?? 0;
        }
        unset($category);

        $dynamicList = array_values($dynamic);
        usort($dynamicList, function (array $a, array $b): int {
            return strcasecmp($a['label'] ?? '', $b['label'] ?? '');
        });

        return array_merge($fixed, $dynamicList);
    }

    public function findById(string $id): ?array
    {
        $id = trim($id);
        if ($id === '') {
            return null;
        }

        foreach ($this->all() as $item) {
            if (($item['id'] ?? '') === $id) {
                return $item;
            }
        }

        return null;
    }

    public function filterByCategorySlug(string $slug): array
    {
        $slug = trim($slug);
        if ($slug === '') {
            return [];
        }

        $filtered = array_filter($this->all(), function (array $item) use ($slug): bool {
            return ($item['category_slug'] ?? '') === $slug;
        });

        return array_values($filtered);
    }

    public function filterBySourceId(string $sourceId): array
    {
        $sourceId = trim($sourceId);
        if ($sourceId === '') {
            return [];
        }

        $filtered = array_filter($this->all(), function (array $item) use ($sourceId): bool {
            return ($item['source_id'] ?? '') === $sourceId;
        });

        return array_values($filtered);
    }

    private function loadItems(): array
    {
        if ($this->pdo === null) {
            return [];
        }

        try {
            return $this->loadItemsFromDb();
        } catch (\Exception $e) {
            return [];
        }
    }

    private function loadItemsFromDb(): array
    {
        $stmt = $this->pdo->query(
            'SELECT id, source_id, title, link, summary, published_at, author, category, categories_json, image_url, raw_guid, raw_extra_json, fetched_at FROM news_items'
        );
        $rows = $stmt === false ? [] : $stmt->fetchAll(PDO::FETCH_ASSOC);
        if (!is_array($rows)) {
            return [];
        }

        $items = [];
        foreach ($rows as $row) {
            $categories = [];
            $rawExtra = [];
            if (!empty($row['categories_json'])) {
                $decoded = json_decode((string) $row['categories_json'], true);
                if (is_array($decoded)) {
                    $categories = $decoded;
                }
            }
            if (!empty($row['raw_extra_json'])) {
                $decoded = json_decode((string) $row['raw_extra_json'], true);
                if (is_array($decoded)) {
                    $rawExtra = $decoded;
                }
            }

            $items[] = [
                'id' => (string) ($row['id'] ?? ''),
                'source_id' => (string) ($row['source_id'] ?? ''),
                'title' => $row['title'] ?? null,
                'link' => $row['link'] ?? null,
                'summary' => $row['summary'] ?? null,
                'published_at' => $row['published_at'] ?? null,
                'author' => $row['author'] ?? null,
                'category' => $row['category'] ?? null,
                'categories' => $categories,
                'image_url' => $row['image_url'] ?? null,
                'raw_guid' => $row['raw_guid'] ?? null,
                'raw_extra' => $rawExtra,
                'fetched_at' => $row['fetched_at'] ?? null,
            ];
        }

        return $items;
    }

    private function attachDerived(array $items): array
    {
        $sources = $this->loadSourcesIndex();
        $updated = [];

        foreach ($items as $item) {
            $rawCategory = trim((string) ($item['category'] ?? ''));
            $categoryCandidates = [];
            if (!empty($item['categories']) && is_array($item['categories'])) {
                foreach ($item['categories'] as $candidate) {
                    $candidate = trim((string) $candidate);
                    if ($candidate !== '') {
                        $categoryCandidates[] = $candidate;
                    }
                }
            }
            if ($rawCategory !== '') {
                $categoryCandidates[] = $rawCategory;
            }
            $categoryCandidates = array_values(array_unique($categoryCandidates));
            if ($this->fixedCategories !== []) {
                $resolved = $this->resolveCategoryCandidates($categoryCandidates);
                if (($resolved['slug'] ?? '') !== '') {
                    $item['category_label'] = $resolved['label'] ?? '';
                    $item['category_slug'] = $resolved['slug'] ?? '';
                } else {
                    $fallbackLabel = $rawCategory !== '' ? $rawCategory : ($categoryCandidates[0] ?? '');
                    $fallbackLabel = trim((string) $fallbackLabel);
                    $item['category_label'] = $fallbackLabel;
                    $item['category_slug'] = $fallbackLabel !== '' ? $this->slugify($fallbackLabel) : '';
                    if ($item['category_label'] === '') {
                        $item['category_label'] = 'Uncategorized';
                        $item['category_slug'] = 'uncategorized';
                    }
                }
            } else {
                $categoryLabel = $rawCategory !== '' ? $rawCategory : 'Uncategorized';
                $item['category_label'] = $categoryLabel;
                $item['category_slug'] = $this->slugify($categoryLabel);
                if ($item['category_slug'] === '') {
                    $item['category_slug'] = 'uncategorized';
                }
            }

            $sourceId = (string) ($item['source_id'] ?? '');
            $item['source_name'] = $sources[$sourceId]['name'] ?? $sourceId;
            $item['_published_ts'] = $this->publishedTimestamp((string) ($item['published_at'] ?? ''));

            $updated[] = $item;
        }

        return $updated;
    }

    private function loadSourcesIndex(): array
    {
        if ($this->sourcesIndex !== null) {
            return $this->sourcesIndex;
        }

        if ($this->pdo === null) {
            $this->sourcesIndex = [];
            return $this->sourcesIndex;
        }

        try {
            $this->sourcesIndex = $this->loadSourcesIndexFromDb();
        } catch (\Exception $e) {
            $this->sourcesIndex = [];
        }
        return $this->sourcesIndex;
    }

    private function loadSourcesIndexFromDb(): array
    {
        $stmt = $this->pdo->query(
            'SELECT id, name, type, url, country, language, default_image_path, enabled FROM news_sources'
        );
        $rows = $stmt === false ? [] : $stmt->fetchAll(PDO::FETCH_ASSOC);
        if (!is_array($rows)) {
            return [];
        }

        $index = [];
        foreach ($rows as $row) {
            $id = (string) ($row['id'] ?? '');
            if ($id === '') {
                continue;
            }
            $index[$id] = [
                'id' => $id,
                'name' => (string) ($row['name'] ?? ''),
                'type' => (string) ($row['type'] ?? ''),
                'url' => (string) ($row['url'] ?? ''),
                'country' => $row['country'] ?? null,
                'language' => $row['language'] ?? null,
                'default_image_path' => $row['default_image_path'] ?? null,
                'enabled' => !empty($row['enabled']),
            ];
        }

        return $index;
    }

    private function publishedTimestamp(string $value): int
    {
        $value = trim($value);
        if ($value === '') {
            return 0;
        }

        $timestamp = strtotime($value);
        return $timestamp === false ? 0 : $timestamp;
    }

    private function slugify(string $value): string
    {
        $value = trim($value);
        if ($value === '') {
            return '';
        }

        $value = $this->normalizeDiacritics($value);
        if (function_exists('iconv')) {
            $converted = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $value);
            if ($converted !== false) {
                $value = $converted;
            }
        }

        $value = strtolower($value);
        $value = preg_replace('/[^a-z0-9]+/', '-', $value);
        $value = trim($value ?? '', '-');

        return $value;
    }

    private function getNormalizedCategories(): array
    {
        if ($this->normalizedCategories !== null) {
            return $this->normalizedCategories;
        }

        $normalized = [];
        foreach ($this->fixedCategories as $category) {
            if (!is_array($category)) {
                continue;
            }
            $slug = trim((string) ($category['slug'] ?? ''));
            $label = trim((string) ($category['label'] ?? ''));
            if ($slug === '' || $label === '') {
                continue;
            }
            $aliases = $category['aliases'] ?? [];
            if (!is_array($aliases)) {
                $aliases = [];
            }
            $normalized[] = [
                'slug' => $slug,
                'label' => $label,
                'aliases' => $aliases,
            ];
        }

        $this->normalizedCategories = $normalized;
        return $this->normalizedCategories;
    }

    private function resolveCategory(string $rawCategory): array
    {
        $rawCategory = trim($rawCategory);
        if ($rawCategory === '') {
            return ['slug' => '', 'label' => ''];
        }

        $categories = $this->getNormalizedCategories();
        $rawSlug = $this->slugify($rawCategory);
        $normalizedRaw = $this->normalizeCategoryText($rawCategory);

        foreach ($categories as $category) {
            if ($rawSlug !== '' && $rawSlug === $category['slug']) {
                return $category;
            }
            if ($normalizedRaw !== '' && $normalizedRaw === $this->normalizeCategoryText($category['label'])) {
                return $category;
            }
        }

        $training = $this->loadCategoryTrainingIndex();
        if ($normalizedRaw !== '' && isset($training[$normalizedRaw])) {
            $matched = $this->findCategoryBySlug((string) $training[$normalizedRaw], $categories);
            if ($matched !== null) {
                return $matched;
            }
        }

        foreach ($categories as $category) {
            foreach ($category['aliases'] as $alias) {
                if ($this->matchesCategory($normalizedRaw, (string) $alias)) {
                    return $category;
                }
            }
        }

        $fuzzy = $this->resolveCategoryFuzzy($rawCategory, $categories);
        if ($fuzzy !== null) {
            return $fuzzy;
        }

        $keywordMap = $this->categoryKeywordMap();
        foreach ($keywordMap as $slug => $keywords) {
            foreach ($keywords as $keyword) {
                if (!$this->matchesCategory($normalizedRaw, $keyword)) {
                    continue;
                }
                $matched = $this->findCategoryBySlug($slug, $categories);
                if ($matched !== null) {
                    return $matched;
                }
            }
        }

        return ['slug' => '', 'label' => ''];
    }

    private function resolveCategoryCandidates(array $candidates): array
    {
        foreach ($candidates as $candidate) {
            $candidate = trim((string) $candidate);
            if ($candidate === '') {
                continue;
            }
            $resolved = $this->resolveCategory($candidate);
            if (($resolved['slug'] ?? '') !== '') {
                return $resolved;
            }
        }

        return ['slug' => '', 'label' => ''];
    }

    private function findCategoryBySlug(string $slug, array $categories): ?array
    {
        foreach ($categories as $category) {
            if (($category['slug'] ?? '') === $slug) {
                return $category;
            }
        }

        return null;
    }

    private function loadCategoryTrainingIndex(): array
    {
        if ($this->categoryTrainingIndex !== null) {
            return $this->categoryTrainingIndex;
        }

        if ($this->categoryTrainingFile === '' || !file_exists($this->categoryTrainingFile)) {
            $this->categoryTrainingIndex = [];
            return $this->categoryTrainingIndex;
        }

        $raw = file_get_contents($this->categoryTrainingFile);
        if ($raw === false) {
            $this->categoryTrainingIndex = [];
            return $this->categoryTrainingIndex;
        }

        $data = json_decode($raw, true);
        if (!is_array($data)) {
            $this->categoryTrainingIndex = [];
            return $this->categoryTrainingIndex;
        }

        $index = [];
        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $rawKey = trim((string) ($value['raw'] ?? ''));
                $slug = trim((string) ($value['slug'] ?? ''));
            } else {
                $rawKey = trim((string) $key);
                $slug = trim((string) $value);
            }
            if ($rawKey === '' || $slug === '') {
                continue;
            }
            $normalizedKey = $this->normalizeCategoryText($rawKey);
            if ($normalizedKey !== '') {
                $index[$normalizedKey] = $slug;
            }
        }

        $this->categoryTrainingIndex = $index;
        return $this->categoryTrainingIndex;
    }

    private function resolveCategoryFuzzy(string $rawCategory, array $categories): ?array
    {
        $rawCategory = trim($rawCategory);
        if ($rawCategory === '') {
            return null;
        }

        $bestScore = 0.0;
        $bestCategory = null;

        foreach ($categories as $category) {
            $candidates = [$category['label'] ?? ''];
            if (is_array($category['aliases'] ?? null)) {
                $candidates = array_merge($candidates, $category['aliases']);
            }
            foreach ($candidates as $candidate) {
                $score = $this->categorySimilarityScore($rawCategory, (string) $candidate);
                if ($score > $bestScore) {
                    $bestScore = $score;
                    $bestCategory = $category;
                }
            }
        }

        return $bestScore >= 0.86 ? $bestCategory : null;
    }

    private function categorySimilarityScore(string $rawCategory, string $candidate): float
    {
        $raw = $this->normalizeCategoryText($rawCategory);
        $label = $this->normalizeCategoryText($candidate);
        if ($raw === '' || $label === '') {
            return 0.0;
        }

        $similarity = $this->stringSimilarity($raw, $label);
        $tokenScore = $this->tokenOverlapScore($raw, $label);
        return max($similarity, $tokenScore);
    }

    private function stringSimilarity(string $a, string $b): float
    {
        $maxLen = max(strlen($a), strlen($b));
        if ($maxLen === 0) {
            return 0.0;
        }

        $distance = levenshtein($a, $b);
        return 1.0 - ($distance / $maxLen);
    }

    private function tokenOverlapScore(string $raw, string $label): float
    {
        $rawTokens = $this->tokenizeCategoryText($raw);
        $labelTokens = $this->tokenizeCategoryText($label);
        if ($rawTokens === [] || $labelTokens === []) {
            return 0.0;
        }

        $overlap = array_intersect($rawTokens, $labelTokens);
        $denominator = max(count($rawTokens), count($labelTokens));
        return $denominator === 0 ? 0.0 : count($overlap) / $denominator;
    }

    private function tokenizeCategoryText(string $value): array
    {
        $normalized = $this->normalizeCategoryText($value);
        if ($normalized === '') {
            return [];
        }

        $tokens = preg_split('/\\s+/', $normalized);
        $stopWords = [
            'de', 'da', 'do', 'das', 'dos', 'na', 'no', 'nas', 'nos',
            'em', 'e', 'a', 'o', 'as', 'os',
        ];
        $filtered = [];
        foreach ($tokens as $token) {
            $token = trim($token);
            if ($token === '' || in_array($token, $stopWords, true)) {
                continue;
            }
            $filtered[] = $token;
        }

        return array_values(array_unique($filtered));
    }

    private function normalizeCategoryText(string $value): string
    {
        $value = strtolower(trim($value));
        if ($value === '') {
            return '';
        }

        $value = $this->normalizeDiacritics($value);
        if (function_exists('iconv')) {
            $converted = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $value);
            if ($converted !== false) {
                $value = $converted;
            }
        }

        $value = preg_replace('/[^a-z0-9]+/', ' ', $value);
        return trim($value ?? '');
    }

    private function matchesCategory(string $normalizedRaw, string $keyword): bool
    {
        $keyword = $this->normalizeCategoryText($keyword);
        if ($normalizedRaw === '' || $keyword === '') {
            return false;
        }

        return preg_match('/\\b' . preg_quote($keyword, '/') . '\\b/', $normalizedRaw) === 1;
    }

    private function categoryKeywordMap(): array
    {
        return [
            'politica' => ['politica', 'governo', 'parlamento', 'eleicoes', 'eleicao', 'presidente', 'ministro', 'partido'],
            'economia' => ['economia', 'mercado', 'bolsa', 'financas', 'negocios', 'inflacao', 'impostos'],
            'pais' => ['pais', 'nacional', 'portugal', 'sociedade', 'seguranca', 'justica'],
            'desporto' => ['desporto', 'esporte', 'futebol', 'football', 'andebol', 'tenis', 'basquete', 'basket', 'voleibol', 'volei'],
            'cultura' => ['cultura', 'arte', 'cinema', 'musica', 'teatro', 'literatura', 'livros'],
            'tecnologia' => ['tecnologia', 'tech', 'inovacao', 'ciencia', 'digital', 'internet', 'ia'],
            'mundo' => ['mundo', 'internacional'],
            'crise-na-venezuela' => ['venezuela', 'crise na venezuela'],
            'presidenciais-2026' => ['ventura', 'andré ventura', 'marques mendes', 'presidenciais', 'presidenciais 20026', 'eleiçoes presidenciais'],
            'opiniao-outras-fontes' => ['opiniao', 'opinion', 'op-ed', 'editorial', 'cronica']
        ];
    }

    private function normalizeDiacritics(string $value): string
    {
        return strtr($value, [
            'á' => 'a',
            'à' => 'a',
            'ã' => 'a',
            'â' => 'a',
            'ä' => 'a',
            'Á' => 'A',
            'À' => 'A',
            'Ã' => 'A',
            'Â' => 'A',
            'Ä' => 'A',
            'é' => 'e',
            'è' => 'e',
            'ê' => 'e',
            'ë' => 'e',
            'É' => 'E',
            'È' => 'E',
            'Ê' => 'E',
            'Ë' => 'E',
            'í' => 'i',
            'ì' => 'i',
            'î' => 'i',
            'ï' => 'i',
            'Í' => 'I',
            'Ì' => 'I',
            'Î' => 'I',
            'Ï' => 'I',
            'ó' => 'o',
            'ò' => 'o',
            'õ' => 'o',
            'ô' => 'o',
            'ö' => 'o',
            'Ó' => 'O',
            'Ò' => 'O',
            'Õ' => 'O',
            'Ô' => 'O',
            'Ö' => 'O',
            'ú' => 'u',
            'ù' => 'u',
            'û' => 'u',
            'ü' => 'u',
            'Ú' => 'U',
            'Ù' => 'U',
            'Û' => 'U',
            'Ü' => 'U',
            'ç' => 'c',
            'Ç' => 'C',
            'ñ' => 'n',
            'Ñ' => 'N',
            'ý' => 'y',
            'ÿ' => 'y',
            'Ý' => 'Y',
        ]);
    }
}
