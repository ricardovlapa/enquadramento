<?php
declare(strict_types=1);

namespace Tests\Support;

use PDO;

final class TestDatabase
{
    public static function create(): PDO
    {
        $pdo = new PDO('sqlite::memory:');
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        return $pdo;
    }

    public static function createNewsSchema(PDO $pdo): void
    {
        $pdo->exec('
            CREATE TABLE news_sources (
                id TEXT PRIMARY KEY,
                name TEXT NOT NULL,
                type TEXT NOT NULL,
                url TEXT NOT NULL,
                country TEXT NULL,
                language TEXT NULL,
                default_image_path TEXT NULL,
                enabled INTEGER NOT NULL DEFAULT 1
            )
        ');

        $pdo->exec('
            CREATE TABLE news_items (
                id TEXT PRIMARY KEY,
                source_id TEXT NOT NULL,
                title TEXT NULL,
                link TEXT NULL,
                summary TEXT NULL,
                published_at TEXT NULL,
                author TEXT NULL,
                category TEXT NULL,
                categories_json TEXT NULL,
                image_url TEXT NULL,
                raw_guid TEXT NULL,
                raw_extra_json TEXT NULL,
                fetched_at TEXT NULL
            )
        ');
    }

    public static function createOpinionSchema(PDO $pdo): void
    {
        $pdo->exec('
            CREATE TABLE authors (
                id TEXT PRIMARY KEY,
                avatar_path TEXT NULL,
                name TEXT NOT NULL,
                description TEXT NULL,
                created_at TEXT NULL
            )
        ');

        $pdo->exec('
            CREATE TABLE articles (
                id TEXT PRIMARY KEY,
                author_id TEXT NULL,
                slug TEXT NOT NULL,
                title TEXT NULL,
                published_at TEXT NULL,
                intro TEXT NULL,
                content TEXT NULL,
                tags_json TEXT NULL
            )
        ');
    }

    public static function createRedirectSchema(PDO $pdo): void
    {
        $pdo->exec('
            CREATE TABLE redirects (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                token TEXT NOT NULL UNIQUE,
                article_id INTEGER NULL,
                source_url TEXT NOT NULL,
                source_domain TEXT NULL,
                title TEXT NULL,
                image TEXT NULL,
                created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
                expire_at TEXT NULL,
                clicks INTEGER NOT NULL DEFAULT 0
            )
        ');
    }
}
