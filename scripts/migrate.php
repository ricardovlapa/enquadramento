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

$dir = dirname(__DIR__) . '/database';
$files = glob($dir . '/*.sql');
if ($files === false || count($files) === 0) {
    echo "No SQL files found in $dir\n";
    exit(0);
}

foreach ($files as $file) {
    echo "Applying: $file\n";
    $sql = file_get_contents($file);
    if ($sql === false) {
        fwrite(STDERR, "Failed to read $file\n");
        continue;
    }
    try {
        $pdo->exec($sql);
        echo "Applied: $file\n";
    } catch (Exception $e) {
        fwrite(STDERR, "Error applying $file: " . $e->getMessage() . "\n");
    }
}

echo "Migrations complete.\n";
