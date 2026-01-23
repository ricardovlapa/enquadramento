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

$authorsFile = __DIR__ . '/../app/Data/authors.json';
if (!is_file($authorsFile)) {
    fwrite(STDERR, "Authors file not found: $authorsFile\n");
    exit(1);
}

$raw = file_get_contents($authorsFile);
$data = $raw === false ? null : json_decode($raw, true);
if (!is_array($data)) {
    fwrite(STDERR, "Invalid JSON in $authorsFile\n");
    exit(1);
}

$pdo->beginTransaction();
$pdo->exec('DELETE FROM authors');
$stmt = $pdo->prepare(
    'INSERT INTO authors (id, avatar_path, name, description, created_at) VALUES (?, ?, ?, ?, ?)'
);

$count = 0;
foreach ($data as $author) {
    if (!is_array($author)) {
        continue;
    }
    $stmt->execute([
        (string) ($author['id'] ?? ''),
        $author['avatar_path'] ?? null,
        $author['name'] ?? null,
        $author['description'] ?? null,
        $author['created_at'] ?? null,
    ]);
    $count++;
}

$pdo->commit();

echo "Imported $count authors into authors.\n";
