<?php

namespace App\Controller;

class AboutController extends BaseController
{
    public function show(): void
    {
        $about = $this->site['ui']['about'] ?? [];
        $title = (string) ($about['title'] ?? 'Sobre');
        if ($title === '') {
            $title = 'Sobre';
        }
        $description = (string) ($about['description'] ?? 'Sobre o projeto.');
        if ($description === '') {
            $description = 'Sobre o projeto.';
        }

        $this->render('about', [], $title, [
            'description' => $description,
        ]);
    }
}
