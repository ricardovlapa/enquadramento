<?php

namespace App\Controller;

class EditorialPrivacyController extends BaseController
{
    public function show(): void
    {
        $this->render('editorial_privacy', [], 'Nota editorial e de privacidade', [
            'description' => 'Nota editorial e de privacidade.',
        ]);
    }
}
