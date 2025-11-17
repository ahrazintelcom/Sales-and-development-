<?php

namespace App\Core;

class Controller
{
    protected function view(string $template, array $data = []): void
    {
        extract($data);
        $authUser = Auth::user();
        $appConfig = require __DIR__ . '/../../config/app.php';
        include __DIR__ . '/../Views/layout/header.php';
        include __DIR__ . '/../Views/' . $template . '.php';
        include __DIR__ . '/../Views/layout/footer.php';
    }

    protected function renderPartial(string $template, array $data = []): void
    {
        extract($data);
        include __DIR__ . '/../Views/' . $template . '.php';
    }

    protected function redirect(string $route): void
    {
        header('Location: ' . $route);
        exit;
    }
}
