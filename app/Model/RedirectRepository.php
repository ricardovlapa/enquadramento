<?php

namespace App\Model;

use PDO;

class RedirectRepository
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function findByToken(string $token): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM redirects WHERE token = ? LIMIT 1');
        $stmt->execute([$token]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row === false ? null : $row;
    }

    public function findByArticleId(int $articleId): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM redirects WHERE article_id = ? LIMIT 1');
        $stmt->execute([$articleId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row === false ? null : $row;
    }

    public function findBySourceUrl(string $sourceUrl): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM redirects WHERE source_url = ? LIMIT 1');
        $stmt->execute([$sourceUrl]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row === false ? null : $row;
    }

    public function create(string $token, ?int $articleId, string $sourceUrl, ?string $title = null, ?string $image = null): array
    {
        $stmt = $this->pdo->prepare('INSERT INTO redirects (token, article_id, source_url, title, image) VALUES (?, ?, ?, ?, ?)');
        $stmt->execute([$token, $articleId, $sourceUrl, $title, $image]);
        $id = (int) $this->pdo->lastInsertId();
        $stmt = $this->pdo->prepare('SELECT * FROM redirects WHERE id = ?');
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function findOrCreate(?int $articleId, string $sourceUrl, ?string $title = null, ?string $image = null): array
    {
        if ($articleId !== null) {
            $existing = $this->findByArticleId($articleId);
            if ($existing !== null) {
                return $existing;
            }
        }

        $existing = $this->findBySourceUrl($sourceUrl);
        if ($existing !== null) {
            return $existing;
        }

        // generate token
        $token = $this->generateToken(10);
        // ensure uniqueness
        $check = $this->findByToken($token);
        $tries = 0;
        while ($check !== null && $tries < 5) {
            $token = $this->generateToken(10);
            $check = $this->findByToken($token);
            $tries++;
        }

        return $this->create($token, $articleId, $sourceUrl, $title, $image);
    }

    public function incrementClicks(int $id): void
    {
        $stmt = $this->pdo->prepare('UPDATE redirects SET clicks = clicks + 1 WHERE id = ?');
        $stmt->execute([$id]);
    }

    private function generateToken(int $len = 10): string
    {
        $chars = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $max = strlen($chars) - 1;
        $t = '';
        for ($i = 0; $i < $len; $i++) {
            $t .= $chars[random_int(0, $max)];
        }
        return $t;
    }
}
