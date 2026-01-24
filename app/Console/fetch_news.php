<?php
declare(strict_types=1);

require __DIR__ . '/../../vendor/autoload.php';
require __DIR__ . '/../dotenv.php';

load_env([
    dirname(__DIR__, 2) . '/.env',
    dirname(__DIR__, 2) . '/.env.local',
]);

use App\Service\NewsFeedFetcher;
use App\Service\Database;

$pdo = Database::requireConnectionFromEnv();
$fetcher = new NewsFeedFetcher($pdo);
$fetchedAt = gmdate('c');
$items = $fetcher->fetch();

$existingItems = [];
$stmt = $pdo->query('SELECT id, source_id, title, link, summary, published_at, author, category, categories_json, image_url, raw_guid, raw_extra_json, fetched_at FROM news_items');
if ($stmt !== false) {
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    if (is_array($rows)) {
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
            $existingItems[] = [
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
    }
}

$itemsById = [];
foreach ($existingItems as $item) {
    if (!is_array($item)) {
        continue;
    }
    $id = (string) ($item['id'] ?? '');
    if ($id === '') {
        continue;
    }
    $itemsById[$id] = $item;
}

foreach ($items as $item) {
    if (!is_array($item)) {
        continue;
    }
    $id = (string) ($item['id'] ?? '');
    if ($id === '') {
        continue;
    }

    $existing = $itemsById[$id] ?? [];
    $existingFetchedAt = (string) ($existing['fetched_at'] ?? '');
    $item['fetched_at'] = $existingFetchedAt !== '' ? $existingFetchedAt : $fetchedAt;
    $itemsById[$id] = array_merge($existing, $item);
}

$cutoff = time() - (7 * 24 * 60 * 60);
$mergedItems = [];
foreach ($itemsById as $item) {
    $publishedAt = trim((string) ($item['published_at'] ?? ''));
    $fetchedAtValue = trim((string) ($item['fetched_at'] ?? ''));
    $timestamp = $publishedAt !== '' ? strtotime($publishedAt) : false;
    if ($timestamp === false && $fetchedAtValue !== '') {
        $timestamp = strtotime($fetchedAtValue);
    }
    if ($timestamp === false) {
        $timestamp = time();
        $item['fetched_at'] = $fetchedAt;
    }
    if ($timestamp >= $cutoff) {
        $mergedItems[] = $item;
    }
}

usort($mergedItems, function (array $a, array $b): int {
    $aTimestamp = strtotime((string) ($a['published_at'] ?? '')) ?: 0;
    $bTimestamp = strtotime((string) ($b['published_at'] ?? '')) ?: 0;
    return $bTimestamp <=> $aTimestamp;
});

$pdo->beginTransaction();
$pdo->exec('DELETE FROM news_items');
$stmt = $pdo->prepare(
    'INSERT INTO news_items (id, source_id, title, link, summary, published_at, author, category, categories_json, image_url, raw_guid, raw_extra_json, fetched_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
);
foreach ($mergedItems as $item) {
    $categoriesJson = json_encode($item['categories'] ?? [], JSON_UNESCAPED_SLASHES);
    $rawExtraJson = json_encode($item['raw_extra'] ?? [], JSON_UNESCAPED_SLASHES);
    $stmt->execute([
        (string) ($item['id'] ?? ''),
        (string) ($item['source_id'] ?? ''),
        $item['title'] ?? null,
        $item['link'] ?? null,
        $item['summary'] ?? null,
        $item['published_at'] ?? null,
        $item['author'] ?? null,
        $item['category'] ?? null,
        $categoriesJson !== false ? $categoriesJson : '[]',
        $item['image_url'] ?? null,
        $item['raw_guid'] ?? null,
        $rawExtraJson !== false ? $rawExtraJson : '[]',
        $item['fetched_at'] ?? null,
    ]);
}
$pdo->commit();

echo 'Saved ' . count($mergedItems) . " items to the database.\n";
