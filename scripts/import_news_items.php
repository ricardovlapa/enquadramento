#!/usr/bin/env php
<?php

require __DIR__ . '/../vendor/autoload.php';
require __DIR__ . '/../app/dotenv.php';

use App\Service\Database;

load_env([
    dirname(__DIR__) . '/.env',
    dirname(__DIR__) . '/.env.local',
]);

try {
    $pdo = Database::requireConnectionFromEnv();
} catch (RuntimeException $e) {
    fwrite(STDERR, $e->getMessage() . "\n");
    exit(1);
}

$itemsFile = __DIR__ . '/../app/Data/items.json';
if (!is_file($itemsFile)) {
    fwrite(STDERR, "Items file not found: $itemsFile\n");
    exit(1);
}

$raw = file_get_contents($itemsFile);
$data = $raw === false ? null : json_decode($raw, true);
if (!is_array($data)) {
    fwrite(STDERR, "Invalid JSON in $itemsFile\n");
    exit(1);
}

$pdo->beginTransaction();
$pdo->exec('DELETE FROM news_items');
$stmt = $pdo->prepare(
    'INSERT INTO news_items (id, source_id, title, link, summary, published_at, author, category, categories_json, image_url, raw_guid, raw_extra_json, fetched_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
);

$count = 0;
foreach ($data as $item) {
    if (!is_array($item)) {
        continue;
    }
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
    $count++;
}

$pdo->commit();

echo "Imported $count items into news_items.\n";
