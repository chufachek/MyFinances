<?php

namespace Bramus\Router;

class Router
{
    private $routes = [];
    private $before = [];
    private $notFound;
    private $basePath = '';

    public function setBasePath($basePath)
    {
        $this->basePath = rtrim($basePath, '/');
    }

    public function before($methods, $pattern, callable $handler)
    {
        $this->before[] = [
            'methods' => $this->parseMethods($methods),
            'pattern' => $pattern,
            'handler' => $handler,
        ];
    }

    public function get($pattern, callable $handler)
    {
        $this->map('GET', $pattern, $handler);
    }

    public function post($pattern, callable $handler)
    {
        $this->map('POST', $pattern, $handler);
    }

    public function put($pattern, callable $handler)
    {
        $this->map('PUT', $pattern, $handler);
    }

    public function delete($pattern, callable $handler)
    {
        $this->map('DELETE', $pattern, $handler);
    }

    public function map($methods, $pattern, callable $handler)
    {
        $this->routes[] = [
            'methods' => $this->parseMethods($methods),
            'pattern' => $pattern,
            'handler' => $handler,
        ];
    }

    public function set404(callable $handler)
    {
        $this->notFound = $handler;
    }

    public function run()
    {
        $method = isset($_SERVER['REQUEST_METHOD']) ? $_SERVER['REQUEST_METHOD'] : 'GET';
        $requestUri = isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : '/';
        $uri = parse_url($requestUri, PHP_URL_PATH);
        if ($uri === null || $uri === false) {
            $uri = '/';
        }
        if ($this->basePath !== '' && strpos($uri, $this->basePath) === 0) {
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

    private function parseMethods($methods)
    {
        return array_map('trim', explode('|', $methods));
    }

    private function patternMatches($pattern, $uri, $returnParams = false)
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
