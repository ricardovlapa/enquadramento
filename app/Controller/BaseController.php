<?php

namespace App\Controller;

class BaseController
{
    protected array $site;

    public function __construct(array $site)
    {
        $this->site = $site;
    }

    protected function render(string $view, array $data = [], string $title = '', array $meta = []): void
    {
        require_once dirname(__DIR__) . '/helpers.php';
        $site = $this->site;
        extract($data);

        $pageTitle = $title === '' ? $this->site['title'] : $title . ' | ' . $this->site['title'];
        $meta = $this->buildMeta($pageTitle, $meta);

        ob_start();
        require dirname(__DIR__) . '/View/' . $view . '.php';
        $content = ob_get_clean();

        require dirname(__DIR__) . '/View/layout.php';
    }

    protected function getBaseUrl(): string
    {
        $baseUrl = $this->site['baseUrl'] ?? '';
        if ($baseUrl === '') {
            $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
            $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
            $host = preg_replace('/:\d+$/', '', $host);
            $allowedHosts = $this->site['allowedHosts'] ?? [];
            if ($allowedHosts !== []) {
                if (!in_array($host, $allowedHosts, true)) {
                    $host = $allowedHosts[0];
                }
            }
            $baseUrl = $scheme . '://' . $host;
        }

        return rtrim($baseUrl, '/');
    }

    private function buildMeta(string $pageTitle, array $meta): array
    {
        $baseUrl = $this->getBaseUrl();
        $path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?? '/';
        $canonical = $meta['canonical'] ?? ($baseUrl . $path);
        $description = $meta['description'] ?? ($this->site['description'] ?? ($this->site['tagline'] ?? ''));
        $image = $meta['image'] ?? ($this->site['socialImage'] ?? '');
        $type = $meta['type'] ?? 'website';
        $ogLocale = $meta['og']['locale'] ?? ($this->site['ogLocale'] ?? '');
        $robots = $meta['robots'] ?? 'index,follow';

        $og = array_merge([
            'title' => $pageTitle,
            'description' => $description,
            'type' => $type,
            'url' => $canonical,
            'image' => $image,
            'locale' => $ogLocale,
        ], $meta['og'] ?? []);

        $twitter = array_merge([
            'card' => $image !== '' ? 'summary_large_image' : 'summary',
            'title' => $pageTitle,
            'description' => $description,
            'image' => $image,
        ], $meta['twitter'] ?? []);

        $jsonLd = $meta['jsonLd'] ?? [];
        if (!is_array($jsonLd)) {
            $jsonLd = [];
        }

        $webSiteSchema = [
            '@context' => 'https://schema.org',
            '@type' => 'WebSite',
            'name' => $this->site['title'] ?? '',
            'url' => $baseUrl,
            'description' => $description,
            'inLanguage' => $this->site['language'] ?? 'pt-PT',
        ];

        array_unshift($jsonLd, $webSiteSchema);

        return [
            'description' => $description,
            'canonical' => $canonical,
            'robots' => $robots,
            'og' => $og,
            'twitter' => $twitter,
            'jsonLd' => $jsonLd,
        ];
    }
}
