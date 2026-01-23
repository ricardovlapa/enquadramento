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

function generateToken($len = 10) {
    $chars = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $max = strlen($chars) - 1;
    $t = '';
    for ($i = 0; $i < $len; $i++) {
        $t .= $chars[random_int(0, $max)];
    }
    return $t;
}

$sourceUrl = getenv('SEED_SOURCE_URL') ?: 'https://example.com/sample-article';
$title = getenv('SEED_TITLE') ?: 'Sample seeded article';
$image = getenv('SEED_IMAGE') ?: '';

// ensure unique token
$token = generateToken(10);
$stmt = $pdo->prepare('SELECT 1 FROM redirects WHERE token = ?');
$stmt->execute([$token]);
while ($stmt->fetch()) {
    $token = generateToken(10);
    $stmt->execute([$token]);
}

$ins = $pdo->prepare('INSERT INTO redirects (token, article_id, source_url, title, image) VALUES (?, NULL, ?, ?, ?)');
$ins->execute([$token, $sourceUrl, $title, $image]);
$id = (int) $pdo->lastInsertId();

echo "Inserted redirect id=$id token=$token\n";
echo "Share landing: /s/" . $token . "\n";
echo "Redirect URL: /r/" . $token . "\n";
