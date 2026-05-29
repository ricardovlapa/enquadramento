<?php
declare(strict_types=1);

namespace Tests\Model;

use App\Model\NewsRepository;
use PHPUnit\Framework\TestCase;
use Tests\Support\TestDatabase;

final class NewsRepositoryTest extends TestCase
{
    private string $trainingFile;

    protected function setUp(): void
    {
        parent::setUp();

        $trainingFile = tempnam(sys_get_temp_dir(), 'news-training-');
        if ($trainingFile === false) {
            throw new \RuntimeException('Failed to create a temporary training file.');
        }

        $this->trainingFile = $trainingFile;
        $bytesWritten = file_put_contents(
            $this->trainingFile,
            json_encode(
                [
                    ['raw' => 'Tech News', 'slug' => 'tecnologia'],
                ],
                JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE
            )
        );
        if ($bytesWritten === false) {
            throw new \RuntimeException('Failed to write the temporary training file.');
        }
    }

    protected function tearDown(): void
    {
        if (is_file($this->trainingFile)) {
            unlink($this->trainingFile);
        }

        parent::tearDown();
    }

    public function testAllResolvesSourceNamesAndCategoriesAndSortsNewestFirst(): void
    {
        $pdo = TestDatabase::create();
        TestDatabase::createNewsSchema($pdo);

        $pdo->exec("
            INSERT INTO news_sources (id, name, type, url, country, language, default_image_path, enabled) VALUES
            ('source-1', 'Daily Planet', 'rss', 'https://example.com/feed-1.xml', 'PT', 'pt', NULL, 1),
            ('source-2', 'Tech Wire', 'rss', 'https://example.com/feed-2.xml', 'PT', 'pt', NULL, 1)
        ");

        $pdo->exec("
            INSERT INTO news_items (id, source_id, title, link, summary, published_at, author, category, categories_json, image_url, raw_guid, raw_extra_json, fetched_at) VALUES
            ('item-1', 'source-1', 'First', 'https://example.com/1', 'Summary', '2026-03-01 08:00:00', 'Ana', 'Política', '[\"Governo\"]', NULL, NULL, '{}', '2026-03-01 09:00:00'),
            ('item-2', 'source-2', 'Second', 'https://example.com/2', 'Summary', '2026-03-03 08:00:00', 'Bruno', '', '[\"Tech News\"]', NULL, NULL, '{}', '2026-03-03 09:00:00'),
            ('item-3', 'source-2', 'Third', 'https://example.com/3', 'Summary', '', 'Carla', '', '[]', NULL, NULL, '{}', '2026-03-02 09:00:00')
        ");

        $repository = new NewsRepository(
            [
                ['slug' => 'politica', 'label' => 'Política', 'aliases' => ['Governo']],
                ['slug' => 'tecnologia', 'label' => 'Tecnologia', 'aliases' => []],
            ],
            $this->trainingFile,
            $pdo
        );

        $items = $repository->all();

        self::assertSame(
            ['item-2', 'item-1', 'item-3'],
            array_column($items, 'id')
        );
        self::assertSame('Tech Wire', $items[0]['source_name']);
        self::assertSame('Tecnologia', $items[0]['category_label']);
        self::assertSame('tecnologia', $items[0]['category_slug']);
        self::assertSame('Política', $items[1]['category_label']);
        self::assertSame('politica', $items[1]['category_slug']);
        self::assertSame('Uncategorized', $items[2]['category_label']);
        self::assertSame('uncategorized', $items[2]['category_slug']);
        self::assertArrayNotHasKey('_published_ts', $items[0]);
    }

    public function testCategoriesReturnsFixedCategoriesWithCountsAndDynamicFallbacks(): void
    {
        $pdo = TestDatabase::create();
        TestDatabase::createNewsSchema($pdo);

        $pdo->exec("
            INSERT INTO news_sources (id, name, type, url, country, language, default_image_path, enabled) VALUES
            ('source-1', 'Daily Planet', 'rss', 'https://example.com/feed.xml', 'PT', 'pt', NULL, 1)
        ");

        $pdo->exec("
            INSERT INTO news_items (id, source_id, title, link, summary, published_at, author, category, categories_json, image_url, raw_guid, raw_extra_json, fetched_at) VALUES
            ('item-1', 'source-1', 'First', 'https://example.com/1', 'Summary', '2026-03-01 08:00:00', 'Ana', 'Política', '[]', NULL, NULL, '{}', '2026-03-01 09:00:00'),
            ('item-2', 'source-1', 'Second', 'https://example.com/2', 'Summary', '2026-03-02 08:00:00', 'Bruno', '', '[\"Tech News\"]', NULL, NULL, '{}', '2026-03-02 09:00:00'),
            ('item-3', 'source-1', 'Third', 'https://example.com/3', 'Summary', '2026-03-03 08:00:00', 'Carla', '', '[]', NULL, NULL, '{}', '2026-03-03 09:00:00')
        ");

        $repository = new NewsRepository(
            [
                ['slug' => 'politica', 'label' => 'Política', 'aliases' => ['Governo']],
                ['slug' => 'tecnologia', 'label' => 'Tecnologia', 'aliases' => []],
            ],
            $this->trainingFile,
            $pdo
        );

        self::assertSame(
            [
                ['slug' => 'politica', 'label' => 'Política', 'count' => 1],
                ['slug' => 'tecnologia', 'label' => 'Tecnologia', 'count' => 1],
                ['slug' => 'uncategorized', 'label' => 'Uncategorized', 'count' => 1],
            ],
            array_map(
                static fn(array $category): array => [
                    'slug' => $category['slug'],
                    'label' => $category['label'],
                    'count' => $category['count'],
                ],
                $repository->categories()
            )
        );
        self::assertSame(['item-2'], array_column($repository->filterByCategorySlug('tecnologia'), 'id'));
        self::assertSame(
            ['item-3', 'item-2', 'item-1'],
            array_column($repository->filterBySourceId('source-1'), 'id')
        );
    }
}
