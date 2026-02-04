<?php

namespace App\Controller;

class LegalEditorialController extends BaseController
{
    public function editorial(): void
    {
        $this->renderLegal('editorial', 'legal_editorial', 'Nota Editorial', 'Nota editorial.');
    }

    public function terms(): void
    {
        $this->renderLegal('terms', 'legal_terms', 'Termos de Utilização', 'Termos de utilização.');
    }

    public function privacy(): void
    {
        $this->renderLegal('privacy', 'legal_privacy', 'Política de Privacidade', 'Política de privacidade.');
    }

    private function renderLegal(string $key, string $view, string $fallbackTitle, string $fallbackDescription): void
    {
        $data = $this->site['ui'][$key] ?? [];
        $title = (string) ($data['title'] ?? $fallbackTitle);
        if ($title === '') {
            $title = $fallbackTitle;
        }
        $description = (string) ($data['description'] ?? $fallbackDescription);
        if ($description === '') {
            $description = $fallbackDescription;
        }

        $this->render($view, [], $title, [
            'description' => $description,
        ]);
    }
}
