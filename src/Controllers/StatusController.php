<?php

namespace App\Controllers;

use App\Services\AIService;

class StatusController
{
    public function apiStatus(): void
    {
        header('Content-Type: application/json');

        $service = new AIService();
        $result = $service->checkConnection();

        http_response_code($result['connected'] ? 200 : 503);
        echo json_encode($result);
    }
}
