<?php
namespace App;

class Router
{
    private array $routes = [
        'GET' => [],
        'POST' => [],
    ];

    public function get(string $path, callable|array $handler): void
    {
        $this->routes['GET'][$path] = $handler;
    }

    public function post(string $path, callable|array $handler): void
    {
        $this->routes['POST'][$path] = $handler;
    }

    public function dispatch(string $method, string $uri): void
    {
        $path = parse_url($uri, PHP_URL_PATH) ?? '/';
        // Normalize for subdir access (e.g., /maruba when accessed via /maruba/index.php)
        if (!empty(BASE_URL) && str_starts_with($path, BASE_URL)) {
            $path = substr($path, strlen(BASE_URL));
        }
        if (str_starts_with($path, '/index.php')) {
            $path = substr($path, strlen('/index.php')) ?: '/';
        }
        $handler = $this->routes[$method][$path] ?? null;
        if (!$handler) {
            http_response_code(404);
            echo '404 Not Found';
            return;
        }

        if (is_array($handler)) {
            [$class, $methodName] = $handler;
            $instance = new $class();
            $instance->$methodName();
            return;
        }

        $handler();
    }
}
