<?php
declare(strict_types=1);

use App\Model\NewsRepository;
use App\Service\Database;

require __DIR__ . '/../../vendor/autoload.php';
require __DIR__ . '/../dotenv.php';

load_env([
    dirname(__DIR__, 2) . '/.env',
    dirname(__DIR__, 2) . '/.env.local',
]);

$config = require __DIR__ . '/../config.php';

$trainingFile = (string) ($config['newsCategoryTrainingData'] ?? '');
$fixedCategories = $config['newsCategories'] ?? [];

$options = parse_args($argv ?? []);
$limit = $options['limit'] ?? 25;
$minCount = $options['min-count'] ?? 1;
$since = $options['since'] ?? '';
$days = $options['days'] ?? 0;
$outputJson = $options['json'] ?? false;

if (!is_array($fixedCategories)) {
    $fixedCategories = [];
}

$fixedIndex = [];
foreach ($fixedCategories as $category) {
    if (!is_array($category)) {
        continue;
    }
    $slug = trim((string) ($category['slug'] ?? ''));
    if ($slug !== '') {
        $fixedIndex[$slug] = true;
    }
}

$pdo = null;
try {
    $pdo = Database::requireConnectionFromEnv();
} catch (\RuntimeException $e) {
    fwrite(STDERR, $e->getMessage() . "\n");
    exit(1);
}

$news = new NewsRepository($fixedCategories, $trainingFile, $pdo);
$items = $news->all();

$sinceTimestamp = 0;
if ($since !== '') {
    $parsed = strtotime($since);
    if ($parsed !== false) {
        $sinceTimestamp = $parsed;
    }
}
if ($days > 0) {
    $sinceTimestamp = max($sinceTimestamp, strtotime("-{$days} days"));
}

$dynamic = [];
foreach ($items as $item) {
    if (!is_array($item)) {
        continue;
    }
    $slug = trim((string) ($item['category_slug'] ?? ''));
    $label = trim((string) ($item['category_label'] ?? ''));
    if ($slug === '' || $label === '' || isset($fixedIndex[$slug])) {
        continue;
    }
    $publishedAt = (string) ($item['published_at'] ?? '');
    $publishedTs = $publishedAt !== '' ? strtotime($publishedAt) : 0;
    if ($sinceTimestamp > 0 && $publishedTs > 0 && $publishedTs < $sinceTimestamp) {
        continue;
    }
    if (!isset($dynamic[$slug])) {
        $dynamic[$slug] = [
            'slug' => $slug,
            'label' => $label,
            'count' => 0,
            'first_seen' => $publishedTs,
            'last_seen' => $publishedTs,
        ];
    }
    $dynamic[$slug]['count']++;
    if ($publishedTs > 0) {
        if ($dynamic[$slug]['first_seen'] === 0 || $publishedTs < $dynamic[$slug]['first_seen']) {
            $dynamic[$slug]['first_seen'] = $publishedTs;
        }
        if ($dynamic[$slug]['last_seen'] === 0 || $publishedTs > $dynamic[$slug]['last_seen']) {
            $dynamic[$slug]['last_seen'] = $publishedTs;
        }
    }
}

$dynamicList = array_values(array_filter($dynamic, function (array $item) use ($minCount): bool {
    return ($item['count'] ?? 0) >= $minCount;
}));

usort($dynamicList, function (array $a, array $b): int {
    $countCompare = ($b['count'] ?? 0) <=> ($a['count'] ?? 0);
    if ($countCompare !== 0) {
        return $countCompare;
    }
    return strcasecmp((string) ($a['label'] ?? ''), (string) ($b['label'] ?? ''));
});

if ($limit > 0) {
    $dynamicList = array_slice($dynamicList, 0, $limit);
}

if ($outputJson) {
    $payload = [];
    foreach ($dynamicList as $item) {
        $payload[] = format_item($item);
    }
    echo json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n";
    exit(0);
}

if ($dynamicList === []) {
    echo "No dynamic categories found.\n";
    exit(0);
}

foreach ($dynamicList as $item) {
    $formatted = format_item($item);
    $first = $formatted['first_seen'] ?? '-';
    $last = $formatted['last_seen'] ?? '-';
    echo "{$formatted['slug']} | {$formatted['label']} | {$formatted['count']} | {$first} | {$last}\n";
}

function parse_args(array $argv): array
{
    $options = [
        'limit' => 25,
        'min-count' => 1,
        'since' => '',
        'days' => 0,
        'json' => false,
    ];

    foreach ($argv as $arg) {
        if ($arg === '--json') {
            $options['json'] = true;
            continue;
        }
        if (preg_match('/^--limit=(\\d+)$/', $arg, $matches)) {
            $options['limit'] = max(1, (int) $matches[1]);
            continue;
        }
        if (preg_match('/^--min-count=(\\d+)$/', $arg, $matches)) {
            $options['min-count'] = max(1, (int) $matches[1]);
            continue;
        }
        if (preg_match('/^--since=(.+)$/', $arg, $matches)) {
            $options['since'] = trim((string) $matches[1]);
            continue;
        }
        if (preg_match('/^--days=(\\d+)$/', $arg, $matches)) {
            $options['days'] = max(0, (int) $matches[1]);
            continue;
        }
        if ($arg === '--help' || $arg === '-h') {
            echo "Usage: php app/Console/report_dynamic_categories.php [--limit=25] [--min-count=1] [--since=YYYY-MM-DD] [--days=N] [--json]\n";
            exit(0);
        }
    }

    return $options;
}

function format_item(array $item): array
{
    $firstSeen = (int) ($item['first_seen'] ?? 0);
    $lastSeen = (int) ($item['last_seen'] ?? 0);
    return [
        'slug' => (string) ($item['slug'] ?? ''),
        'label' => (string) ($item['label'] ?? ''),
        'count' => (int) ($item['count'] ?? 0),
        'first_seen' => $firstSeen > 0 ? date('Y-m-d', $firstSeen) : null,
        'last_seen' => $lastSeen > 0 ? date('Y-m-d', $lastSeen) : null,
    ];
}
