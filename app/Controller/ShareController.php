<?php

namespace App\Controller;

use App\Model\RedirectRepository;

class ShareController extends BaseController
{
    private RedirectRepository $redirects;

    public function __construct(array $site, RedirectRepository $redirects)
    {
        parent::__construct($site);
        $this->redirects = $redirects;
    }

    public function show(string $token): void
    {
        $token = trim($token);
        if ($token === '') {
            (new NotFoundController($this->site))->show();
            return;
        }

        $row = $this->redirects->findByToken($token);
        if ($row === null) {
            (new NotFoundController($this->site))->show();
            return;
        }

        $title = (string) ($row['title'] ?? ($this->site['title'] ?? ''));
        $isOpinion = str_contains((string) ($row['source_url'] ?? ''), '/opiniao-enquadramento/');
        $desc = (string) ($row['summary'] ?? $row['description'] ?? ($isOpinion ? 'Clique para ler o artigo completo' : 'Clique para ler a notícia original'));
        $imagePath = (string) ($row['image'] ?? ($this->site['socialImage'] ?? ''));

        $base = $this->resolveBaseUrl();
        $image = $this->normalizeImageUrl($base, $imagePath);
        $shareUrl = $base . '/s/' . $token;
        $redirectUrl = '/r/' . $token;

        $meta = [
            'description' => $desc,
            'canonical' => $shareUrl,
            'type' => 'article',
            'image' => $image,
            'og' => [
                'title' => $title,
            ],
            'twitter' => [
                'title' => $title,
            ],
        ];

        $this->render('share_landing', [
            'title' => $title,
            'desc' => $desc,
            'redirectUrl' => $redirectUrl,
            'ctaLabel' => $isOpinion ? 'Ler artigo' : 'Ler notícia',
            'note' => $isOpinion
                ? 'A pré-visualização não redireciona automaticamente. Clique no botão para abrir o artigo.'
                : 'A pré-visualização não redireciona automaticamente. Clique no botão para abrir a fonte original.',
        ], $title, $meta);
    }

    private function normalizeImageUrl(string $base, string $path): string
    {
        if ($path === '') {
            return '';
        }

        if (preg_match('#^https?://#i', $path) === 1) {
            return $path;
        }

        return rtrim($base, '/') . '/' . ltrim($path, '/');
    }

    private function resolveBaseUrl(): string
    {
        $configuredBase = rtrim((string) ($this->site['baseUrl'] ?? ''), '/');
        if ($configuredBase !== '') {
            return $configuredBase;
        }

        $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
            || ($_SERVER['SERVER_PORT'] ?? '') === '443'
            ? 'https'
            : 'http';
        $host = $_SERVER['HTTP_HOST'] ?? ($_SERVER['SERVER_NAME'] ?? 'localhost');

        return $scheme . '://' . $host;
    }
}
