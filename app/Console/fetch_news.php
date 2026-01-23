<?php
declare(strict_types=1);

require __DIR__ . '/../../vendor/autoload.php';

use App\Service\NewsFeedFetcher;
use App\Service\Database;

$sourcesFile = __DIR__ . '/../Data/newsFeed.json';
$outputFile = __DIR__ . '/../Data/items.json';

$pdo = null;
$dsn = getenv('DB_DSN') ?: getenv('MYSQL_DSN') ?: '';
if ($dsn !== '') {
    $pdo = Database::getConnectionFromEnv();
    if ($pdo === null) {
        $error = Database::getLastError();
        if ($error !== null) {
            fwrite(STDERR, $error . " Falling back to JSON.\n");
        }
    }
}

$fetcher = new NewsFeedFetcher($sourcesFile, $pdo);
$fetchedAt = gmdate('c');
$items = $fetcher->fetch();

$existingItems = [];
if ($pdo !== null) {
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
} elseif (is_file($outputFile)) {
    $raw = file_get_contents($outputFile);
    $decoded = $raw === false ? null : json_decode($raw, true);
    if (is_array($decoded)) {
        $existingItems = $decoded;
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

$payload = json_encode(
    $mergedItems,
    JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES
);
if ($payload === false) {
    throw new RuntimeException('Failed to encode items as JSON.');
}
if (file_put_contents($outputFile, $payload . PHP_EOL) === false) {
    throw new RuntimeException('Failed to write items to ' . $outputFile);
}

if ($pdo !== null) {
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
}

echo 'Saved ' . count($mergedItems) . ' items to ' . $outputFile . PHP_EOL;
