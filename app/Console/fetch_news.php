<?php
declare(strict_types=1);

require __DIR__ . '/../../vendor/autoload.php';
require __DIR__ . '/../dotenv.php';

load_env([
    dirname(__DIR__, 2) . '/.env',
    dirname(__DIR__, 2) . '/.env.local',
]);

use App\Service\Database;
use App\Service\NewsFeedFetcher;

$readIntFromEnv = static function (string $key, int $default, int $min): int {
    $raw = getenv($key);
    if ($raw === false) {
        return $default;
    }

    $raw = trim((string) $raw);
    if ($raw === '' || preg_match('/^-?\d+$/', $raw) !== 1) {
        return $default;
    }

    $value = (int) $raw;
    if ($value < $min) {
        return $default;
    }

    return $value;
};

$maxAttempts = $readIntFromEnv('FETCH_NEWS_MAX_ATTEMPTS', 5, 1);
$baseDelaySeconds = $readIntFromEnv('FETCH_NEWS_RETRY_BASE_DELAY_SECONDS', 1, 0);
$maxErrorsToShow = 5;

$formatError = static function (array $error): string {
    $type = trim((string) ($error['type'] ?? 'feed_error'));
    $message = trim((string) ($error['message'] ?? 'Feed error.'));
    $sourceId = trim((string) ($error['source_id'] ?? ''));
    $url = trim((string) ($error['url'] ?? ''));
    $detail = trim((string) ($error['detail'] ?? ''));

    $parts = ['[' . $type . ']', $message];
    if ($sourceId !== '') {
        $parts[] = 'source_id=' . $sourceId;
    }
    if ($url !== '') {
        $parts[] = 'url=' . $url;
    }
    if ($detail !== '') {
        $parts[] = 'detail=' . $detail;
    }

    return implode(' | ', $parts);
};

$printErrorSummary = static function (
    int $attempt,
    int $maxAttempts,
    array $errors,
    int $maxErrorsToShow,
    callable $formatError
): void {
    fwrite(
        STDERR,
        'Attempt ' . $attempt . '/' . $maxAttempts
        . ' completed with ' . count($errors) . " feed error(s).\n"
    );

    $errorsToShow = array_slice($errors, 0, $maxErrorsToShow);
    foreach ($errorsToShow as $error) {
        fwrite(STDERR, ' - ' . $formatError(is_array($error) ? $error : []) . PHP_EOL);
    }

    $remaining = count($errors) - count($errorsToShow);
    if ($remaining > 0) {
        fwrite(STDERR, ' - ... and ' . $remaining . " more error(s).\n");
    }
};

$runAttempt = static function (PDO $pdo, NewsFeedFetcher $fetcher): array {
    $fetchedAt = gmdate('c');
    $cutoff = time() - (7 * 24 * 60 * 60);
    $items = $fetcher->fetch();

    $fetchedById = [];
    foreach ($items as $item) {
        if (!is_array($item)) {
            continue;
        }
        $id = (string) ($item['id'] ?? '');
        if ($id === '') {
            continue;
        }

        $publishedAt = trim((string) ($item['published_at'] ?? ''));
        $timestamp = $publishedAt !== '' ? strtotime($publishedAt) : false;
        if ($timestamp === false) {
            $timestamp = time();
        }
        if ($timestamp < $cutoff) {
            continue;
        }

        $fetchedById[$id] = $item;
    }

    if ($fetchedById === []) {
        return [
            'fetched_count' => 0,
            'upserted' => 0,
            'unchanged' => 0,
        ];
    }

    $existingById = [];
    $ids = array_keys($fetchedById);
    foreach (array_chunk($ids, 500) as $chunk) {
        $placeholders = implode(',', array_fill(0, count($chunk), '?'));
        $stmt = $pdo->prepare(
            'SELECT id, source_id, title, link, summary, published_at, author, category, categories_json, image_url, raw_guid, raw_extra_json, fetched_at FROM news_items WHERE id IN ('
            . $placeholders . ')'
        );
        if ($stmt === false) {
            throw new RuntimeException('Failed to prepare existing items query.');
        }
        $stmt->execute($chunk);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        if (!is_array($rows)) {
            continue;
        }

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
            $id = (string) ($row['id'] ?? '');
            if ($id === '') {
                continue;
            }
            $existingById[$id] = [
                'id' => $id,
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

    $normalizeText = static function ($value): string {
        return trim((string) ($value ?? ''));
    };

    $normalizeItemForCompare = static function (array $item) use ($normalizeText): array {
        $categories = $item['categories'] ?? [];
        if (!is_array($categories)) {
            $categories = [];
        }
        $categories = array_values($categories);
        sort($categories, SORT_STRING);

        $rawExtra = $item['raw_extra'] ?? [];
        if (!is_array($rawExtra)) {
            $rawExtra = [];
        }
        ksort($rawExtra);

        return [
            'source_id' => $normalizeText($item['source_id'] ?? ''),
            'title' => $normalizeText($item['title'] ?? null),
            'link' => $normalizeText($item['link'] ?? null),
            'summary' => $normalizeText($item['summary'] ?? null),
            'published_at' => $normalizeText($item['published_at'] ?? null),
            'author' => $normalizeText($item['author'] ?? null),
            'category' => $normalizeText($item['category'] ?? null),
            'categories' => $categories,
            'image_url' => $normalizeText($item['image_url'] ?? null),
            'raw_guid' => $normalizeText($item['raw_guid'] ?? null),
            'raw_extra' => $rawExtra,
            'fetched_at' => $normalizeText($item['fetched_at'] ?? null),
        ];
    };

    $itemsToUpsert = [];
    $unchanged = 0;

    foreach ($fetchedById as $id => $item) {
        $existing = $existingById[$id] ?? null;
        $existingFetchedAt = is_array($existing) ? trim((string) ($existing['fetched_at'] ?? '')) : '';
        $item['fetched_at'] = $existingFetchedAt !== '' ? $existingFetchedAt : $fetchedAt;

        if (!is_array($existing)) {
            $itemsToUpsert[] = $item;
            continue;
        }

        $candidate = $normalizeItemForCompare($item);
        $current = $normalizeItemForCompare($existing);

        if ($candidate !== $current) {
            $itemsToUpsert[] = $item;
        } else {
            $unchanged++;
        }
    }

    $upserted = 0;
    if ($itemsToUpsert !== []) {
        $pdo->beginTransaction();
        $stmt = $pdo->prepare(
            'INSERT INTO news_items (id, source_id, title, link, summary, published_at, author, category, categories_json, image_url, raw_guid, raw_extra_json, fetched_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
             ON DUPLICATE KEY UPDATE
               source_id = VALUES(source_id),
               title = VALUES(title),
               link = VALUES(link),
               summary = VALUES(summary),
               published_at = VALUES(published_at),
               author = VALUES(author),
               category = VALUES(category),
               categories_json = VALUES(categories_json),
               image_url = VALUES(image_url),
               raw_guid = VALUES(raw_guid),
               raw_extra_json = VALUES(raw_extra_json),
               fetched_at = VALUES(fetched_at)'
        );
        if ($stmt === false) {
            throw new RuntimeException('Failed to prepare upsert statement.');
        }

        foreach ($itemsToUpsert as $item) {
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
            $upserted++;
        }
        $pdo->commit();
    }

    return [
        'fetched_count' => count($fetchedById),
        'upserted' => $upserted,
        'unchanged' => $unchanged,
    ];
};

$pdo = Database::requireConnectionFromEnv();
$fetcher = new NewsFeedFetcher($pdo);
$lastResult = [
    'fetched_count' => 0,
    'upserted' => 0,
    'unchanged' => 0,
];
$lastErrors = [];
$attemptsUsed = 0;

for ($attempt = 1; $attempt <= $maxAttempts; $attempt++) {
    $attemptsUsed = $attempt;
    $lastResult = $runAttempt($pdo, $fetcher);
    $lastErrors = $fetcher->getErrors();

    if (!$fetcher->hasErrors()) {
        break;
    }

    $printErrorSummary($attempt, $maxAttempts, $lastErrors, $maxErrorsToShow, $formatError);

    if ($attempt < $maxAttempts) {
        $delay = $baseDelaySeconds * (2 ** ($attempt - 1));
        if ($delay > 0) {
            fwrite(STDERR, 'Retrying fetch_news in ' . $delay . " second(s)...\n");
            sleep($delay);
        }
    }
}

if ($lastErrors !== []) {
    fwrite(
        STDERR,
        'feed fetch retries exhausted after ' . $attemptsUsed . '/' . $maxAttempts . " attempt(s).\n"
    );
    $printErrorSummary($attemptsUsed, $maxAttempts, $lastErrors, $maxErrorsToShow, $formatError);
    exit(1);
}

if ($attemptsUsed > 1) {
    echo 'Fetch succeeded on attempt ' . $attemptsUsed . '/' . $maxAttempts . ".\n";
}

if (($lastResult['fetched_count'] ?? 0) === 0) {
    echo "No recent items fetched.\n";
    exit(0);
}

echo 'Fetched ' . $lastResult['fetched_count']
    . ' recent items (' . $lastResult['upserted']
    . ' upserted, ' . $lastResult['unchanged']
    . " unchanged).\n";
