<?php
require dirname(__DIR__) . '/vendor/autoload.php';
require dirname(__DIR__) . '/app/dotenv.php';

load_env([
    dirname(__DIR__) . '/.env',
    dirname(__DIR__) . '/.env.local',
]);

header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: SAMEORIGIN');
header('Referrer-Policy: strict-origin-when-cross-origin');
header('Permissions-Policy: geolocation=(), microphone=(), camera=()');
header(
    "Content-Security-Policy: default-src 'self'; " .
    "img-src 'self' https: data:; " .
    "style-src 'self' 'unsafe-inline' https://fonts.googleapis.com; " .
    "font-src https://fonts.gstatic.com; " .
    "script-src 'self' 'unsafe-inline' https://www.googletagmanager.com; " .
    "connect-src 'self' https://www.google-analytics.com https://region1.google-analytics.com https://www.googletagmanager.com; " .
    "base-uri 'self'; " .
    "form-action 'self'; " .
    "frame-ancestors 'self'"
);

use App\Model\NewsRepository;
use App\Model\OpinionRepository;
use App\Model\RedirectRepository;
use App\Router;
use App\Service\Database;

$config = require dirname(__DIR__) . '/app/config.php';

$site = $config['site'];
$pdo = Database::getConnectionFromEnv();

$news = new NewsRepository(
    $config['newsCategories'] ?? [],
    $config['newsCategoryTrainingData'] ?? '',
    $pdo
);
$opinions = new OpinionRepository($pdo);

$redirects = $pdo !== null ? new RedirectRepository($pdo) : null;

$router = new Router();
$registerRoutes = require dirname(__DIR__) . '/app/routes.php';
$registerRoutes($router, $site, $news, $opinions, $redirects);

$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$path = rtrim($path, '/');
$path = $path === '' ? '/' : $path;

$router->dispatch($_SERVER['REQUEST_METHOD'], $path);
