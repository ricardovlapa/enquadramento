<?php
declare(strict_types=1);

require __DIR__ . '/../../vendor/autoload.php';
require __DIR__ . '/../dotenv.php';

load_env([
    dirname(__DIR__, 2) . '/.env',
    dirname(__DIR__, 2) . '/.env.local',
]);

use App\Service\Database;

$pdo = Database::requireConnectionFromEnv();
$cutoff = time() - (7 * 24 * 60 * 60);
$cutoffIso = gmdate('c', $cutoff);

$stmt = $pdo->prepare(
    'DELETE FROM news_items
     WHERE (published_at IS NOT NULL AND published_at <> "" AND published_at < ?)
        OR ((published_at IS NULL OR published_at = "") AND fetched_at IS NOT NULL AND fetched_at <> "" AND fetched_at < ?)'
);
if ($stmt === false) {
    throw new RuntimeException('Failed to prepare cleanup statement.');
}

$stmt->execute([$cutoffIso, $cutoffIso]);
$deleted = $stmt->rowCount();

echo 'Deleted ' . $deleted . " items older than 7 days.\n";
