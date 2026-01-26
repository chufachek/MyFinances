<?php

namespace Bramus\Router;

class Router
{
    private array $routes = [];
    private array $before = [];
    private $notFound;
    private string $basePath = '';

    public function setBasePath(string $basePath): void
    {
        $this->basePath = rtrim($basePath, '/');
    }

    public function before(string $methods, string $pattern, callable $handler): void
    {
        $this->before[] = [
            'methods' => $this->parseMethods($methods),
            'pattern' => $pattern,
            'handler' => $handler,
        ];
    }

    public function get(string $pattern, callable $handler): void
    {
        $this->map('GET', $pattern, $handler);
    }

    public function post(string $pattern, callable $handler): void
    {
        $this->map('POST', $pattern, $handler);
    }

    public function put(string $pattern, callable $handler): void
    {
        $this->map('PUT', $pattern, $handler);
    }

    public function delete(string $pattern, callable $handler): void
    {
        $this->map('DELETE', $pattern, $handler);
    }

    public function map(string $methods, string $pattern, callable $handler): void
    {
        $this->routes[] = [
            'methods' => $this->parseMethods($methods),
            'pattern' => $pattern,
            'handler' => $handler,
        ];
    }

    public function set404(callable $handler): void
    {
        $this->notFound = $handler;
    }

    public function run(): void
    {
        $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
        $uri = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?? '/';
        if ($this->basePath !== '' && str_starts_with($uri, $this->basePath)) {
            $uri = substr($uri, strlen($this->basePath));
        }
        $uri = '/' . ltrim($uri, '/');

        foreach ($this->before as $before) {
            if (!in_array($method, $before['methods'], true)) {
                continue;
            }
            if ($this->patternMatches($before['pattern'], $uri)) {
                ($before['handler'])();
            }
        }

        foreach ($this->routes as $route) {
            if (!in_array($method, $route['methods'], true)) {
                continue;
            }

            $params = $this->patternMatches($route['pattern'], $uri, true);
            if ($params === false) {
                continue;
            }

            ($route['handler'])(...$params);
            return;
        }

        if ($this->notFound) {
            ($this->notFound)();
            return;
        }

        http_response_code(404);
        echo 'Not Found';
    }

    private function parseMethods(string $methods): array
    {
        return array_map('trim', explode('|', $methods));
    }

    private function patternMatches(string $pattern, string $uri, bool $returnParams = false): array|bool
    {
        $regex = preg_replace('#\{([^/]+)\}#', '(?P<$1>[^/]+)', $pattern);
        $regex = '#^' . $regex . '$#';
        if (!preg_match($regex, $uri, $matches)) {
            return false;
        }
        if (!$returnParams) {
            return true;
        }
        $params = [];
        foreach ($matches as $key => $value) {
            if (is_string($key)) {
                $params[] = $value;
            }
        }
        return $params;
    }
}
