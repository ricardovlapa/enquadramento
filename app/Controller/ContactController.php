<?php

namespace App\Controller;

class ContactController extends BaseController
{
    public function show(): void
    {
        $contact = $this->site['ui']['contact'] ?? [];
        $title = (string) ($contact['title'] ?? 'Contactos');
        if ($title === '') {
            $title = 'Contactos';
        }
        $description = (string) ($contact['description'] ?? 'Contactos.');
        if ($description === '') {
            $description = 'Contactos.';
        }

        $this->render('contact', [], $title, [
            'description' => $description,
        ]);
    }
}
