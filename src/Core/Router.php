<?php

namespace App\Core;

class Router
{
    private array $routes = [];

    public function get(string $path, callable $handler): void
    {
        $this->routes['GET'][$path] = $handler;
    }

    public function post(string $path, callable $handler): void
    {
        $this->routes['POST'][$path] = $handler;
    }

    public function dispatch(string $method, string $path)
    {
        $path = explode('?', $path)[0];
        $handler = $this->routes[$method][$path] ?? null;
        if (!$handler) {
            http_response_code(404);
            echo 'Page not found';
            return;
        }
        return call_user_func($handler);
    }
}
