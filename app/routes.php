<?php

use App\Controller\HomeController;
use App\Controller\NewsController;
use App\Controller\OpinionController;
use App\Controller\EditorialPrivacyController;
use App\Controller\NotFoundController;
use App\Controller\ShareController;
use App\Model\NewsRepository;
use App\Model\OpinionRepository;
use App\Model\RedirectRepository;
use App\Router;

return function (Router $router, array $site, NewsRepository $news, OpinionRepository $opinions, ?RedirectRepository $redirects = null): void {
    $router->get('/', function () use ($site, $news, $opinions) {
        (new HomeController($site, $news, $opinions))->show();
    });

    $router->get('/nota-editorial-e-privacidade', function () use ($site) {
        (new EditorialPrivacyController($site))->show();
    });

    $router->get('/opiniao-enquadramento', function () use ($site, $opinions) {
        (new OpinionController($site, $opinions))->index();
    });

    $router->get('/opiniao-enquadramento/{slug}', function (array $params) use ($site, $opinions) {
        (new OpinionController($site, $opinions))->show($params['slug'] ?? '');
    });

    $router->get('/todas-as-noticias', function () use ($site, $news) {
        (new NewsController($site, $news))->index();
    });

    $router->get('/noticias/categoria/opiniao-enquadramento', function () use ($site, $opinions) {
        (new OpinionController($site, $opinions))->index();
    });

    $router->get('/noticias/categoria/{category}', function (array $params) use ($site, $news) {
        (new NewsController($site, $news))->category($params['category'] ?? '');
    });

    if ($redirects !== null) {
        $router->get('/share/{id}', function (array $params) use ($site, $news, $redirects) {
            $id = $params['id'] ?? '';
            $item = $news->findById((string) $id);
            if ($item === null) {
                http_response_code(404);
                if (isset($_GET['json']) && $_GET['json'] === '1') {
                    header('Content-Type: application/json; charset=utf-8');
                    echo json_encode(['error' => 'Not found']);
                    return;
                }
                (new NotFoundController())->show();
                return;
            }

            $articleId = null;
            if (isset($item['id']) && is_numeric((string) $item['id'])) {
                $articleId = (int) $item['id'];
            }
            $sourceUrl = $item['link'] ?? ($item['source_url'] ?? '');
            $title = $item['title'] ?? '';
            $image = $item['image_url'] ?? '';

            $row = $redirects->findOrCreate($articleId, $sourceUrl, $title, $image);
            $token = $row['token'] ?? null;
            if ($token === null) {
                http_response_code(500);
                if (isset($_GET['json']) && $_GET['json'] === '1') {
                    header('Content-Type: application/json; charset=utf-8');
                    echo json_encode(['error' => 'Failed to create share link.']);
                    return;
                }
                echo 'Failed to create share link.';
                return;
            }

            $configuredBase = rtrim((string) ($site['baseUrl'] ?? ''), '/');
            if ($configuredBase !== '') {
                $base = $configuredBase;
            } else {
                $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || ($_SERVER['SERVER_PORT'] ?? '') === '443' ? 'https' : 'http';
                $host = $_SERVER['HTTP_HOST'] ?? ($_SERVER['SERVER_NAME'] ?? 'localhost');
                $base = $scheme . '://' . $host;
            }

            $shareUrl = $base . '/s/' . $token;
            $redirectUrl = $base . '/r/' . $token;

            if (isset($_GET['json']) && $_GET['json'] === '1') {
                header('Content-Type: application/json; charset=utf-8');
                echo json_encode([
                    'share_url' => $shareUrl,
                    'redirect_url' => $redirectUrl,
                    'token' => $token,
                ]);
                return;
            }

            header('Location: ' . $shareUrl, true, 302);
            exit;
        });
        $router->get('/s/{token}', function (array $params) use ($site, $redirects) {
            (new ShareController($site, $redirects))->show($params['token'] ?? '');
        });

        $router->get('/r/{token}', function (array $params) use ($redirects) {
            $token = $params['token'] ?? '';
            $row = $redirects->findByToken($token);
            if ($row === null) {
                http_response_code(404);
                (new NotFoundController())->show();
                return;
            }
            $redirects->incrementClicks((int) $row['id']);
            header('Location: ' . $row['source_url'], true, 302);
            exit;
        });
    }
};
