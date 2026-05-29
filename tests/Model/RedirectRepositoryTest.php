<?php
declare(strict_types=1);

namespace Tests\Model;

use App\Model\RedirectRepository;
use PHPUnit\Framework\TestCase;
use Tests\Support\TestDatabase;

final class RedirectRepositoryTest extends TestCase
{
    public function testFindOrCreateCreatesAndReusesRedirects(): void
    {
        $pdo = TestDatabase::create();
        TestDatabase::createRedirectSchema($pdo);

        $repository = new RedirectRepository($pdo);

        $created = $repository->findOrCreate(
            123,
            'https://example.com/articles/first',
            'First article',
            'https://example.com/first.jpg'
        );

        self::assertIsArray($created);
        self::assertSame('123', (string) $created['article_id']);
        self::assertSame('https://example.com/articles/first', $created['source_url']);
        self::assertSame('First article', $created['title']);
        self::assertSame('https://example.com/first.jpg', $created['image']);
        self::assertSame(10, strlen((string) $created['token']));

        $sameArticle = $repository->findOrCreate(
            123,
            'https://example.com/articles/changed',
            'Changed title'
        );
        self::assertSame($created['id'], $sameArticle['id']);

        $sameSource = $repository->findOrCreate(
            null,
            'https://example.com/articles/first',
            'Duplicate source'
        );
        self::assertSame($created['id'], $sameSource['id']);
    }

    public function testFindMethodsAndIncrementClicksWorkWithStoredRows(): void
    {
        $pdo = TestDatabase::create();
        TestDatabase::createRedirectSchema($pdo);

        $pdo->exec("
            INSERT INTO redirects (token, article_id, source_url, source_domain, title, image, clicks) VALUES
            ('abc123xyz0', 77, 'https://example.com/source', 'example.com', 'Stored title', 'https://example.com/image.jpg', 2)
        ");

        $repository = new RedirectRepository($pdo);

        $byToken = $repository->findByToken('abc123xyz0');
        $byArticleId = $repository->findByArticleId(77);
        $bySourceUrl = $repository->findBySourceUrl('https://example.com/source');

        self::assertNotNull($byToken);
        self::assertSame($byToken['id'], $byArticleId['id']);
        self::assertSame($byToken['id'], $bySourceUrl['id']);
        self::assertNull($repository->findByToken('missing-token'));
        self::assertNull($repository->findByArticleId(999));
        self::assertNull($repository->findBySourceUrl('https://example.com/missing'));

        $repository->incrementClicks((int) $byToken['id']);

        $updated = $repository->findByToken('abc123xyz0');
        self::assertNotNull($updated);
        self::assertSame('3', (string) $updated['clicks']);
    }

    public function testCreateStoresNullableFields(): void
    {
        $pdo = TestDatabase::create();
        TestDatabase::createRedirectSchema($pdo);

        $repository = new RedirectRepository($pdo);

        $created = $repository->create('token12345A', null, 'https://example.com/source');

        self::assertSame('token12345A', $created['token']);
        self::assertNull($created['article_id']);
        self::assertSame('https://example.com/source', $created['source_url']);
        self::assertNull($created['title']);
        self::assertNull($created['image']);
        self::assertSame('0', (string) $created['clicks']);
    }
}
