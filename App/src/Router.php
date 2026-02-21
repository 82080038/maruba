<?php
namespace App;

class Router
{
    public array $routes = [
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

    public function getRoutes(): array
    {
        return $this->routes;
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
        
        // Remove leading slash
        $path = ltrim($path, '/');
        
        // Handle empty URI (root URL)
        if ($path === '') {
            $path = '/';
        }
        
        // Debug: Log the final path
        error_log("Router dispatch: method=$method, uri=$uri, path=$path");
        
        // Check if route exists
        $handler = $this->routes[$method][$path] ?? null;
        if (!$handler) {
            // Try with leading slash
            $pathWithSlash = '/' . $path;
            $handler = $this->routes[$method][$pathWithSlash] ?? null;
            if ($handler) {
                $path = $pathWithSlash;
            }
        }
        
        if (!$handler) {
            error_log("Available routes for $method: " . implode(', ', array_keys($this->routes[$method])));
            http_response_code(404);
            echo '404 Not Found';
            error_log("No handler found for path: $path");
            return;
        }
        
        // Debug: Log handler type
        if (is_array($handler)) {
            error_log("Handler type: array - " . $handler[0] . "::" . $handler[1]);
        } else {
            error_log("Handler type: callable");
        }
        
        if (is_array($handler)) {
            [$class, $methodName] = $handler;
            try {
                $instance = new $class();
                $instance->$methodName();
                return;
            } catch (Exception $e) {
                error_log("Controller error: " . $e->getMessage());
                http_response_code(500);
                echo '500 Internal Server Error';
                return;
            } catch (Error $e) {
                error_log("Controller fatal error: " . $e->getMessage());
                http_response_code(500);
                echo '500 Internal Server Error';
                return;
            }
        }

        if (is_callable($handler)) {
            try {
                $handler();
                return;
            } catch (Exception $e) {
                error_log("Handler error: " . $e->getMessage());
                http_response_code(500);
                echo '500 Internal Server Error';
                return;
            } catch (Error $e) {
                error_log("Handler fatal error: " . $e->getMessage());
                http_response_code(500);
                echo '500 Internal Server Error';
                return;
            }
        }
        
        error_log("No valid handler found for path: $path");
        http_response_code(404);
        echo '404 Not Found';
    }
}
