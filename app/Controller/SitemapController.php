<?php

namespace App\Controller;

class SitemapController extends BaseController
{
    public function __construct(array $site)
    {
        parent::__construct($site);
    }

    public function show(): void
    {
        header('Content-Type: application/xml; charset=UTF-8');

        $baseUrl = $this->getBaseUrl();
        if ($baseUrl !== '' && !preg_match('~^https?://~i', $baseUrl)) {
            $baseUrl = 'https://' . $baseUrl;
        }
        $baseUrl = rtrim($baseUrl, '/');

        $urls = [
            ['loc' => $baseUrl . '/', 'priority' => '1.0'],
            ['loc' => $baseUrl . '/nota-editorial-e-de-privacidade', 'priority' => '0.5'],
        ];

        $esc = fn(string $value): string => htmlspecialchars($value, ENT_XML1, 'UTF-8');

        echo "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n";
        echo "<urlset xmlns=\"http://www.sitemaps.org/schemas/sitemap/0.9\">\n";
        foreach ($urls as $url) {
            echo "  <url>\n";
            echo "    <loc>" . $esc($url['loc']) . "</loc>\n";
            if (!empty($url['lastmod'])) {
                echo "    <lastmod>" . $esc($url['lastmod']) . "</lastmod>\n";
            }
            if (!empty($url['priority'])) {
                echo "    <priority>" . $esc($url['priority']) . "</priority>\n";
            }
            echo "  </url>\n";
        }
        echo "</urlset>\n";
    }
}
