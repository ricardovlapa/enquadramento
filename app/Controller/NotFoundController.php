<?php

namespace App\Controller;

class NotFoundController extends BaseController
{
    public function __construct(array $site = [])
    {
        if ($site === []) {
            $config = require dirname(__DIR__) . '/config.php';
            $site = $config['site'];
        }
        parent::__construct($site);
    }

    public function show(): void
    {
        http_response_code(404);
        $this->render('not_found', [], 'Not Found', [
            'robots' => 'noindex,nofollow',
            'description' => 'Página não encontrada.',
        ]);
    }
}
