<?php

namespace App\Model;

use PDO;

class OpinionRepository
{
    private ?array $articlesCache = null;
    private ?array $authorsCache = null;
    private ?array $authorsIndex = null;
    private ?PDO $pdo;

    public function __construct(?PDO $pdo = null)
    {
        $this->pdo = $pdo;
    }

    public function allArticles(): array
    {
        if ($this->articlesCache !== null) {
            return $this->articlesCache;
        }

        $articles = $this->loadArticles();
        $articles = $this->attachAuthors($articles);

        usort($articles, function (array $a, array $b): int {
            return ($b['_published_ts'] ?? 0) <=> ($a['_published_ts'] ?? 0);
        });

        foreach ($articles as &$article) {
            unset($article['_published_ts']);
        }
        unset($article);

        $this->articlesCache = $articles;
        return $this->articlesCache;
    }

    public function findArticleBySlug(string $slug): ?array
    {
        $slug = trim($slug);
        if ($slug === '') {
            return null;
        }

        foreach ($this->allArticles() as $article) {
            if (($article['slug'] ?? '') === $slug) {
                return $article;
            }
        }

        return null;
    }

    public function authors(): array
    {
        if ($this->authorsCache !== null) {
            return $this->authorsCache;
        }

        $authorsIndex = $this->buildAuthorsIndex();
        foreach ($authorsIndex as &$author) {
            $author['articles'] = [];
        }
        unset($author);

        foreach ($this->loadArticles() as $article) {
            $authorId = (string) ($article['author_id'] ?? '');
            if ($authorId === '' || !isset($authorsIndex[$authorId])) {
                continue;
            }
            $article['_published_ts'] = $this->publishedTimestamp((string) ($article['published_at'] ?? ''));
            $authorsIndex[$authorId]['articles'][] = $article;
        }

        foreach ($authorsIndex as &$author) {
            usort($author['articles'], function (array $a, array $b): int {
                return ($b['_published_ts'] ?? 0) <=> ($a['_published_ts'] ?? 0);
            });
            foreach ($author['articles'] as &$article) {
                unset($article['_published_ts']);
            }
            unset($article);
        }
        unset($author);

        $authors = array_values($authorsIndex);
        usort($authors, function (array $a, array $b): int {
            return strcasecmp((string) ($a['name'] ?? ''), (string) ($b['name'] ?? ''));
        });

        $this->authorsCache = $authors;
        return $this->authorsCache;
    }

    public function articlesByAuthor(string $authorId, string $excludeId = ''): array
    {
        $authorId = trim($authorId);
        if ($authorId === '') {
            return [];
        }

        $articles = array_filter($this->allArticles(), function (array $article) use ($authorId, $excludeId): bool {
            if (($article['author_id'] ?? '') !== $authorId) {
                return false;
            }
            if ($excludeId !== '' && ($article['id'] ?? '') === $excludeId) {
                return false;
            }
            return true;
        });

        return array_values($articles);
    }

    private function loadArticles(): array
    {
        if ($this->pdo === null) {
            return [];
        }

        try {
            return $this->loadArticlesFromDb();
        } catch (\Exception $e) {
            return [];
        }
    }

    private function loadAuthors(): array
    {
        if ($this->pdo === null) {
            return [];
        }

        try {
            return $this->loadAuthorsFromDb();
        } catch (\Exception $e) {
            return [];
        }
    }

    private function loadArticlesFromDb(): array
    {
        $stmt = $this->pdo->query(
            'SELECT id, author_id, slug, title, published_at, intro, content, tags_json FROM articles'
        );
        $rows = $stmt === false ? [] : $stmt->fetchAll(PDO::FETCH_ASSOC);
        if (!is_array($rows)) {
            return [];
        }

        $articles = [];
        foreach ($rows as $row) {
            $tags = [];
            if (!empty($row['tags_json'])) {
                $decoded = json_decode((string) $row['tags_json'], true);
                if (is_array($decoded)) {
                    $tags = $decoded;
                }
            }
            $articles[] = [
                'id' => (string) ($row['id'] ?? ''),
                'author_id' => $row['author_id'] ?? null,
                'slug' => $row['slug'] ?? null,
                'title' => $row['title'] ?? null,
                'published_at' => $row['published_at'] ?? null,
                'intro' => $row['intro'] ?? null,
                'content' => $row['content'] ?? null,
                'tags' => $tags,
            ];
        }

        return $articles;
    }

    private function loadAuthorsFromDb(): array
    {
        $stmt = $this->pdo->query(
            'SELECT id, avatar_path, name, description, created_at FROM authors'
        );
        $rows = $stmt === false ? [] : $stmt->fetchAll(PDO::FETCH_ASSOC);
        if (!is_array($rows)) {
            return [];
        }

        $authors = [];
        foreach ($rows as $row) {
            $authors[] = [
                'id' => (string) ($row['id'] ?? ''),
                'avatar_path' => $row['avatar_path'] ?? null,
                'name' => $row['name'] ?? null,
                'description' => $row['description'] ?? null,
                'created_at' => $row['created_at'] ?? null,
            ];
        }

        return $authors;
    }

    private function buildAuthorsIndex(): array
    {
        if ($this->authorsIndex !== null) {
            return $this->authorsIndex;
        }

        $index = [];
        foreach ($this->loadAuthors() as $author) {
            if (!is_array($author)) {
                continue;
            }
            $id = (string) ($author['id'] ?? '');
            if ($id === '') {
                continue;
            }
            $index[$id] = $author;
        }

        $this->authorsIndex = $index;
        return $this->authorsIndex;
    }

    private function attachAuthors(array $articles): array
    {
        $authors = $this->buildAuthorsIndex();
        $updated = [];

        foreach ($articles as $article) {
            $authorId = (string) ($article['author_id'] ?? '');
            $article['author'] = $authors[$authorId] ?? null;
            $article['_published_ts'] = $this->publishedTimestamp((string) ($article['published_at'] ?? ''));
            $updated[] = $article;
        }

        return $updated;
    }

    private function publishedTimestamp(string $value): int
    {
        $value = trim($value);
        if ($value === '') {
            return 0;
        }

        $timestamp = strtotime($value);
        return $timestamp === false ? 0 : $timestamp;
    }
}
