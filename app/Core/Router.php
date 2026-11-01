<?php
declare(strict_types=1);

namespace App\Core;

use App\Middleware\AuthMiddleware;
use App\Middleware\RoleMiddleware;

class Router
{
    private array $routes = [];
    private array $groupMiddleware = [];

    public function get(string $path, array $handler, array $middleware = []): void
    {
        $this->add('GET', $path, $handler, $middleware);
    }

    public function post(string $path, array $handler, array $middleware = []): void
    {
        $this->add('POST', $path, $handler, $middleware);
    }

    public function group(array $middleware, callable $callback): void
    {
        $previous = $this->groupMiddleware;
        $this->groupMiddleware = array_merge($previous, $middleware);
        $callback($this);
        $this->groupMiddleware = $previous;
    }

    private function add(string $method, string $path, array $handler, array $middleware): void
    {
        $regex = preg_replace('#\{(\w+)\}#', '(?P<$1>[^/]+)', $path);
        $this->routes[] = [
            'method'     => $method,
            'regex'      => '#^' . $regex . '$#',
            'handler'    => $handler,
            'middleware' => array_merge($this->groupMiddleware, $middleware),
        ];
    }

    public function dispatch(Request $request): void
    {
        $path = rtrim($request->path(), '/') ?: '/';
        $method = $request->method();

        foreach ($this->routes as $route) {
            if ($route['method'] !== $method || !preg_match($route['regex'], $path, $m)) {
                continue;
            }

            // Global CSRF check on every state-changing request
            if ($method !== 'GET' && !Csrf::verify($request)) {
                abort(403, 'Your session expired. Go back, refresh the page and try again.');
            }

            foreach ($route['middleware'] as $mw) {
                $this->runMiddleware($mw, $request);
            }

            $params = array_filter($m, 'is_string', ARRAY_FILTER_USE_KEY);
            [$class, $action] = $route['handler'];
            $controller = new $class();
            $controller->$action(...array_values($params));
            return;
        }

        abort(404);
    }

    private function runMiddleware(string $name, Request $request): void
    {
        if ($name === 'auth') {
            (new AuthMiddleware())->handle($request);
        } elseif (str_starts_with($name, 'role:')) {
            (new RoleMiddleware(explode(',', substr($name, 5))))->handle($request);
        }
    }
}
