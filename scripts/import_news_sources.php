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

$sourcesFile = __DIR__ . '/../app/Data/newsFeed.json';
if (!is_file($sourcesFile)) {
    fwrite(STDERR, "Sources file not found: $sourcesFile\n");
    exit(1);
}

$raw = file_get_contents($sourcesFile);
$data = $raw === false ? null : json_decode($raw, true);
if (!is_array($data) || !isset($data['sources']) || !is_array($data['sources'])) {
    fwrite(STDERR, "Invalid JSON in $sourcesFile\n");
    exit(1);
}

$pdo->beginTransaction();
$pdo->exec('DELETE FROM news_sources');
$stmt = $pdo->prepare(
    'INSERT INTO news_sources (id, name, type, url, country, language, default_image_path, enabled) VALUES (?, ?, ?, ?, ?, ?, ?, ?)'
);

$count = 0;
foreach ($data['sources'] as $source) {
    if (!is_array($source)) {
        continue;
    }
    $stmt->execute([
        (string) ($source['id'] ?? ''),
        (string) ($source['name'] ?? ''),
        (string) ($source['type'] ?? 'rss'),
        (string) ($source['url'] ?? ''),
        $source['country'] ?? null,
        $source['language'] ?? null,
        $source['default_image_path'] ?? null,
        !empty($source['enabled']) ? 1 : 0,
    ]);
    $count++;
}

$pdo->commit();

echo "Imported $count sources into news_sources.\n";
