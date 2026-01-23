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

$articlesFile = __DIR__ . '/../app/Data/articles.json';
if (!is_file($articlesFile)) {
    fwrite(STDERR, "Articles file not found: $articlesFile\n");
    exit(1);
}

$raw = file_get_contents($articlesFile);
$data = $raw === false ? null : json_decode($raw, true);
if (!is_array($data)) {
    fwrite(STDERR, "Invalid JSON in $articlesFile\n");
    exit(1);
}

$pdo->beginTransaction();
$pdo->exec('DELETE FROM articles');
$stmt = $pdo->prepare(
    'INSERT INTO articles (id, author_id, slug, title, published_at, intro, content, tags_json) VALUES (?, ?, ?, ?, ?, ?, ?, ?)'
);

$count = 0;
foreach ($data as $article) {
    if (!is_array($article)) {
        continue;
    }
    $tagsJson = json_encode($article['tags'] ?? [], JSON_UNESCAPED_SLASHES);
    $stmt->execute([
        (string) ($article['id'] ?? ''),
        $article['author_id'] ?? null,
        (string) ($article['slug'] ?? ''),
        $article['title'] ?? null,
        $article['published_at'] ?? null,
        $article['intro'] ?? null,
        $article['content'] ?? null,
        $tagsJson !== false ? $tagsJson : '[]',
    ]);
    $count++;
}

$pdo->commit();

echo "Imported $count articles into articles.\n";
