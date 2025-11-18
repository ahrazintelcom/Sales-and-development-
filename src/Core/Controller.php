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

    protected function redirect(string $path): void
    {
        if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
            header('Location: ' . $path);
            exit;
        }

        $normalized = '/' . ltrim($path, '/');
        if (defined('BASE_URL') && BASE_URL !== '') {
            $normalized = BASE_URL . $normalized;
        }

        header('Location: ' . $normalized);
        exit;
    }
}
