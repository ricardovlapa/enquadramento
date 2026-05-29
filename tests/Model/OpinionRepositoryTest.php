<?php
declare(strict_types=1);

namespace Tests\Model;

use App\Model\OpinionRepository;
use PHPUnit\Framework\TestCase;
use Tests\Support\TestDatabase;

final class OpinionRepositoryTest extends TestCase
{
    public function testAllArticlesAttachesAuthorsAndSortsNewestFirst(): void
    {
        $pdo = TestDatabase::create();
        TestDatabase::createOpinionSchema($pdo);

        $pdo->exec("
            INSERT INTO authors (id, avatar_path, name, description, created_at) VALUES
            ('author-1', '/ana.png', 'Ana', 'Columnist', '2026-01-01'),
            ('author-2', '/bruno.png', 'Bruno', 'Reporter', '2026-01-02')
        ");

        $pdo->exec("
            INSERT INTO articles (id, author_id, slug, title, published_at, intro, content, tags_json) VALUES
            ('article-1', 'author-1', 'older-piece', 'Older Piece', '2026-03-01 08:00:00', 'Intro', 'Content', '[\"politics\"]'),
            ('article-2', 'author-2', 'newer-piece', 'Newer Piece', '2026-03-02 08:00:00', 'Intro', 'Content', '[\"economy\"]'),
            ('article-3', 'missing-author', 'orphaned-piece', 'Orphaned Piece', 'invalid-date', 'Intro', 'Content', '[]')
        ");

        $repository = new OpinionRepository($pdo);

        $articles = $repository->allArticles();

        self::assertSame(
            ['article-2', 'article-1', 'article-3'],
            array_column($articles, 'id')
        );
        self::assertSame('Bruno', $articles[0]['author']['name']);
        self::assertSame('Ana', $articles[1]['author']['name']);
        self::assertNull($articles[2]['author']);
        self::assertArrayNotHasKey('_published_ts', $articles[0]);
    }

    public function testAuthorsGroupsArticlesPerAuthorAndSupportsExclusions(): void
    {
        $pdo = TestDatabase::create();
        TestDatabase::createOpinionSchema($pdo);

        $pdo->exec("
            INSERT INTO authors (id, avatar_path, name, description, created_at) VALUES
            ('author-2', '/bruno.png', 'Bruno', 'Reporter', '2026-01-02'),
            ('author-1', '/ana.png', 'Ana', 'Columnist', '2026-01-01')
        ");

        $pdo->exec("
            INSERT INTO articles (id, author_id, slug, title, published_at, intro, content, tags_json) VALUES
            ('article-1', 'author-1', 'first-piece', 'First Piece', '2026-03-01 08:00:00', 'Intro', 'Content', '[]'),
            ('article-2', 'author-1', 'second-piece', 'Second Piece', '2026-03-03 08:00:00', 'Intro', 'Content', '[]'),
            ('article-3', 'author-2', 'third-piece', 'Third Piece', '2026-03-02 08:00:00', 'Intro', 'Content', '[]')
        ");

        $repository = new OpinionRepository($pdo);

        $authors = $repository->authors();

        self::assertSame(['Ana', 'Bruno'], array_column($authors, 'name'));
        self::assertSame(
            ['article-2', 'article-1'],
            array_column($authors[0]['articles'], 'id')
        );
        self::assertSame(
            ['article-1'],
            array_column($repository->articlesByAuthor('author-1', 'article-2'), 'id')
        );
    }
}
