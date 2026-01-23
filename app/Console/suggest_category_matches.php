<?php
declare(strict_types=1);

require __DIR__ . '/../../vendor/autoload.php';
require __DIR__ . '/../dotenv.php';

load_env([
    dirname(__DIR__, 2) . '/.env',
    dirname(__DIR__, 2) . '/.env.local',
]);

$config = require __DIR__ . '/../config.php';

$itemsFile = (string) ($config['newsData'] ?? '');
$trainingFile = (string) ($config['newsCategoryTrainingData'] ?? '');
$sourcesFile = (string) ($config['newsSourcesData'] ?? '');
$fixedCategories = $config['newsCategories'] ?? [];

$options = parse_args($argv ?? []);
$limit = $options['limit'] ?? 3;
$minScore = $options['min-score'] ?? 0.6;
$outputJson = $options['json'] ?? false;
$writeMode = $options['write'] ?? false;
$diffMode = $options['diff'] ?? false;
$confirmMode = $options['confirm'] ?? false;
$showSources = $options['show-sources'] ?? false;
$sourceLimit = $options['source-limit'] ?? 3;

if (!is_array($fixedCategories)) {
    $fixedCategories = [];
}

$items = load_json_file($itemsFile);
$trainingIndex = load_training_index($trainingFile);
$sourcesIndex = load_sources_index($sourcesFile);

$rawCounts = [];
$rawSources = [];
foreach ($items as $item) {
    if (!is_array($item)) {
        continue;
    }
    $candidates = [];
    if (!empty($item['categories']) && is_array($item['categories'])) {
        foreach ($item['categories'] as $candidate) {
            $candidate = trim((string) $candidate);
            if ($candidate !== '') {
                $candidates[] = $candidate;
            }
        }
    }
    $rawCategory = trim((string) ($item['category'] ?? ''));
    if ($rawCategory !== '') {
        $candidates[] = $rawCategory;
    }
    foreach ($candidates as $candidate) {
        $rawCounts[$candidate] = ($rawCounts[$candidate] ?? 0) + 1;
        $sourceId = trim((string) ($item['source_id'] ?? ''));
        if ($sourceId !== '') {
            $rawSources[$candidate][$sourceId] = ($rawSources[$candidate][$sourceId] ?? 0) + 1;
        }
    }
}

$unknown = [];
foreach ($rawCounts as $raw => $count) {
    if (matches_fixed_category($raw, $fixedCategories, $trainingIndex)) {
        continue;
    }
    $unknown[$raw] = $count;
}

if ($unknown === []) {
    echo "No unknown categories found.\n";
    exit(0);
}

arsort($unknown);

$suggestions = [];
foreach ($unknown as $raw => $count) {
    $best = suggest_matches($raw, $fixedCategories, $limit, $minScore);
    $suggestions[$raw] = [
        'count' => $count,
        'matches' => $best,
    ];
}

if ($outputJson) {
    $payload = [];
    foreach ($suggestions as $raw => $data) {
        if ($data['matches'] === []) {
            continue;
        }
        $payload[$raw] = $data['matches'][0]['slug'];
    }
    echo json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n";
    if ($writeMode) {
        echo "Skipped writing because --json was used.\n";
    }
    exit(0);
}

if ($writeMode || $diffMode) {
    $changes = plan_training_changes($trainingFile, $suggestions, $minScore);
    if ($diffMode) {
        output_training_diff($changes);
        if (!$writeMode) {
            exit(0);
        }
    }
    if ($writeMode && ($confirmMode || $diffMode)) {
        $shouldWrite = confirm_write();
        if (!$shouldWrite) {
            echo "Aborted. No changes written.\n";
            exit(0);
        }
    }
    $written = apply_training_changes($trainingFile, $changes);
    echo "Added {$written} mappings to training file.\n";
    exit(0);
}

echo "Unknown categories: " . count($unknown) . "\n";
foreach ($suggestions as $raw => $data) {
    $matches = $data['matches'];
    if ($matches === []) {
    echo "- {$raw} (count: {$data['count']}) -> no suggestions\n";
    if ($showSources) {
        output_source_breakdown($raw, $rawSources, $sourcesIndex, $sourceLimit);
    }
    continue;
}
    $parts = [];
    foreach ($matches as $match) {
        $parts[] = $match['slug'] . ' (' . $match['label'] . ') [' . number_format($match['score'], 2) . ']';
    }
    echo "- {$raw} (count: {$data['count']}) -> " . implode(', ', $parts) . "\n";
    if ($showSources) {
        output_source_breakdown($raw, $rawSources, $sourcesIndex, $sourceLimit);
    }
}

function parse_args(array $argv): array
{
    $options = [
        'limit' => 3,
        'min-score' => 0.6,
        'json' => false,
        'write' => false,
        'diff' => false,
        'confirm' => false,
        'show-sources' => false,
        'source-limit' => 3,
    ];

    foreach ($argv as $arg) {
        if ($arg === '--json') {
            $options['json'] = true;
            continue;
        }
        if ($arg === '--write') {
            $options['write'] = true;
            continue;
        }
        if ($arg === '--diff') {
            $options['diff'] = true;
            continue;
        }
        if ($arg === '--confirm') {
            $options['confirm'] = true;
            continue;
        }
        if ($arg === '--show-sources') {
            $options['show-sources'] = true;
            continue;
        }
        if (preg_match('/^--source-limit=(\\d+)$/', $arg, $matches)) {
            $options['source-limit'] = max(1, (int) $matches[1]);
            continue;
        }
        if (preg_match('/^--limit=(\\d+)$/', $arg, $matches)) {
            $options['limit'] = max(1, (int) $matches[1]);
            continue;
        }
        if (preg_match('/^--min-score=([0-9]*\\.?[0-9]+)$/', $arg, $matches)) {
            $options['min-score'] = (float) $matches[1];
            continue;
        }
        if ($arg === '--help' || $arg === '-h') {
            echo "Usage: php app/Console/suggest_category_matches.php [--limit=N] [--min-score=0.6] [--json] [--write] [--diff] [--confirm] [--show-sources] [--source-limit=N]\n";
            exit(0);
        }
    }

    return $options;
}

function load_json_file(string $path): array
{
    if ($path === '' || !is_file($path)) {
        return [];
    }

    $raw = file_get_contents($path);
    if ($raw === false) {
        return [];
    }

    $data = json_decode($raw, true);
    return is_array($data) ? $data : [];
}

function load_training_index(string $path): array
{
    $data = load_json_file($path);
    if (!is_array($data)) {
        return [];
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
        $normalizedKey = normalize_category_text($rawKey);
        if ($normalizedKey !== '') {
            $index[$normalizedKey] = $slug;
        }
    }

    return $index;
}

function load_sources_index(string $path): array
{
    $data = load_json_file($path);
    if (!is_array($data)) {
        return [];
    }

    $sources = $data['sources'] ?? $data;
    if (!is_array($sources)) {
        return [];
    }

    $index = [];
    foreach ($sources as $source) {
        if (!is_array($source)) {
            continue;
        }
        $id = trim((string) ($source['id'] ?? ''));
        if ($id === '') {
            continue;
        }
        $index[$id] = [
            'name' => (string) ($source['name'] ?? $id),
            'url' => (string) ($source['url'] ?? ''),
        ];
    }

    return $index;
}

function output_source_breakdown(
    string $raw,
    array $rawSources,
    array $sourcesIndex,
    int $sourceLimit
): void {
    $sourceCounts = $rawSources[$raw] ?? [];
    if (!is_array($sourceCounts) || $sourceCounts === []) {
        return;
    }

    arsort($sourceCounts);
    $parts = [];
    $count = 0;
    foreach ($sourceCounts as $sourceId => $hits) {
        $source = $sourcesIndex[$sourceId] ?? ['name' => $sourceId, 'url' => ''];
        $label = $source['name'] !== '' ? $source['name'] : $sourceId;
        $url = $source['url'] ?? '';
        $parts[] = $label . ' [' . $hits . '] ' . $url;
        $count++;
        if ($count >= $sourceLimit) {
            break;
        }
    }

    if ($parts === []) {
        return;
    }

    echo "  sources: " . implode('; ', $parts) . "\n";
}

function plan_training_changes(string $path, array $suggestions, float $minScore): array
{
    if ($path === '') {
        return [
            'data' => [],
            'adds' => [],
        ];
    }

    $data = load_json_file($path);
    if (!is_array($data)) {
        $data = [];
    }

    $existingKeys = [];
    foreach ($data as $key => $value) {
        if (is_array($value)) {
            $rawKey = trim((string) ($value['raw'] ?? ''));
        } else {
            $rawKey = trim((string) $key);
        }
        if ($rawKey === '') {
            continue;
        }
        $existingKeys[normalize_category_text($rawKey)] = true;
    }

    $adds = [];
    foreach ($suggestions as $raw => $dataItem) {
        $normalizedRaw = normalize_category_text($raw);
        if ($normalizedRaw === '' || isset($existingKeys[$normalizedRaw])) {
            continue;
        }
        $matches = $dataItem['matches'] ?? [];
        if ($matches === []) {
            continue;
        }
        $best = $matches[0];
        if (($best['score'] ?? 0) < $minScore) {
            continue;
        }
        $adds[$raw] = $best['slug'];
    }

    return [
        'data' => $data,
        'adds' => $adds,
    ];
}

function apply_training_changes(string $path, array $changes): int
{
    if ($path === '') {
        return 0;
    }

    $data = $changes['data'] ?? [];
    $adds = $changes['adds'] ?? [];
    if (!is_array($data) || !is_array($adds) || $adds === []) {
        return 0;
    }

    foreach ($adds as $raw => $slug) {
        $data[$raw] = $slug;
    }

    $payload = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    if ($payload === false) {
        return 0;
    }
    if (file_put_contents($path, $payload . "\n") === false) {
        return 0;
    }

    return count($adds);
}

function output_training_diff(array $changes): void
{
    $adds = $changes['adds'] ?? [];
    if (!is_array($adds) || $adds === []) {
        echo "No new mappings to add.\n";
        return;
    }

    ksort($adds);
    foreach ($adds as $raw => $slug) {
        echo "+ {$raw} => {$slug}\n";
    }
}

function confirm_write(): bool
{
    if (!function_exists('readline')) {
        return false;
    }

    $answer = readline('Write these changes to the training file? [y/N] ');
    $answer = strtolower(trim($answer));
    return $answer === 'y' || $answer === 'yes';
}

function matches_fixed_category(string $raw, array $fixedCategories, array $trainingIndex): bool
{
    $raw = trim($raw);
    if ($raw === '') {
        return true;
    }

    $normalizedRaw = normalize_category_text($raw);
    if ($normalizedRaw !== '' && isset($trainingIndex[$normalizedRaw])) {
        return true;
    }

    $rawSlug = slugify($raw);
    foreach ($fixedCategories as $category) {
        if (!is_array($category)) {
            continue;
        }
        $slug = trim((string) ($category['slug'] ?? ''));
        $label = trim((string) ($category['label'] ?? ''));
        if ($slug !== '' && $rawSlug === $slug) {
            return true;
        }
        if ($normalizedRaw !== '' && $label !== '' && $normalizedRaw === normalize_category_text($label)) {
            return true;
        }
        $aliases = $category['aliases'] ?? [];
        if (!is_array($aliases)) {
            $aliases = [];
        }
        foreach ($aliases as $alias) {
            if ($normalizedRaw !== '' && $normalizedRaw === normalize_category_text((string) $alias)) {
                return true;
            }
        }
    }

    return false;
}

function suggest_matches(string $raw, array $fixedCategories, int $limit, float $minScore): array
{
    $matches = [];
    foreach ($fixedCategories as $category) {
        if (!is_array($category)) {
            continue;
        }
        $label = (string) ($category['label'] ?? '');
        $aliases = $category['aliases'] ?? [];
        if (!is_array($aliases)) {
            $aliases = [];
        }
        $candidates = array_merge([$label], $aliases);
        $bestScore = 0.0;
        foreach ($candidates as $candidate) {
            $score = category_similarity_score($raw, (string) $candidate);
            if ($score > $bestScore) {
                $bestScore = $score;
            }
        }
        if ($bestScore >= $minScore) {
            $matches[] = [
                'slug' => (string) ($category['slug'] ?? ''),
                'label' => $label,
                'score' => $bestScore,
            ];
        }
    }

    usort($matches, function (array $a, array $b): int {
        return $b['score'] <=> $a['score'];
    });

    return array_slice($matches, 0, $limit);
}

function category_similarity_score(string $rawCategory, string $candidate): float
{
    $raw = normalize_category_text($rawCategory);
    $label = normalize_category_text($candidate);
    if ($raw === '' || $label === '') {
        return 0.0;
    }

    $similarity = string_similarity($raw, $label);
    $tokenScore = token_overlap_score($raw, $label);
    return max($similarity, $tokenScore);
}

function string_similarity(string $a, string $b): float
{
    $maxLen = max(strlen($a), strlen($b));
    if ($maxLen === 0) {
        return 0.0;
    }

    $distance = levenshtein($a, $b);
    return 1.0 - ($distance / $maxLen);
}

function token_overlap_score(string $raw, string $label): float
{
    $rawTokens = tokenize_category_text($raw);
    $labelTokens = tokenize_category_text($label);
    if ($rawTokens === [] || $labelTokens === []) {
        return 0.0;
    }

    $overlap = array_intersect($rawTokens, $labelTokens);
    $denominator = max(count($rawTokens), count($labelTokens));
    return $denominator === 0 ? 0.0 : count($overlap) / $denominator;
}

function tokenize_category_text(string $value): array
{
    $normalized = normalize_category_text($value);
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

function normalize_category_text(string $value): string
{
    $value = strtolower(trim($value));
    if ($value === '') {
        return '';
    }

    $value = normalize_diacritics($value);
    if (function_exists('iconv')) {
        $converted = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $value);
        if ($converted !== false) {
            $value = $converted;
        }
    }

    $value = preg_replace('/[^a-z0-9]+/', ' ', $value);
    return trim($value ?? '');
}

function slugify(string $value): string
{
    $value = trim($value);
    if ($value === '') {
        return '';
    }

    $value = normalize_diacritics($value);
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

function normalize_diacritics(string $value): string
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
