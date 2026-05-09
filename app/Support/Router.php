<?php

declare(strict_types=1);

namespace App\Support;

final class Router
{
    private array $routes = [];

    public function get(string $path, callable $handler): void
    {
        $this->match(['GET'], $path, $handler);
    }

    public function post(string $path, callable $handler): void
    {
        $this->match(['POST'], $path, $handler);
    }

    public function match(array $methods, string $path, callable $handler): void
    {
        $normalizedPath = $this->normalizePath($path);

        foreach ($methods as $method) {
            $this->routes[strtoupper($method)][$normalizedPath] = $handler;
        }
    }

    public function dispatch(string $method, string $uri): void
    {
        $normalizedPath = $this->normalizePath($this->stripBasePath(parse_url($uri, PHP_URL_PATH) ?: '/'));
        $method = strtoupper($method);

        $handler = $this->routes[$method][$normalizedPath] ?? null;

        if ($handler === null) {
            Response::html('<h1>404 Not Found</h1>', 404);
        }

        call_user_func($handler);
    }

    private function normalizePath(string $path): string
    {
        $path = '/' . trim($path, '/');

        return $path === '//' ? '/' : $path;
    }

    private function stripBasePath(string $path): string
    {
        $appUrl = Env::get('APP_URL', 'http://localhost');
        $basePath = parse_url($appUrl, PHP_URL_PATH) ?: '';
        $basePath = rtrim($basePath, '/');

        if ($basePath !== '' && str_starts_with($path, $basePath)) {
            $trimmed = substr($path, strlen($basePath));
            return $trimmed === '' ? '/' : $trimmed;
        }

        return $path;
    }
}
