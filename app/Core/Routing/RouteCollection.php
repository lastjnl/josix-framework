<?php

namespace Josix\Core\Routing;

class RouteCollection
{
    private array $routes = [];

    public function add(
        string $method,
        string $path,
        string $class,
        string $action,
        ?string $name = null
    ): void {
        [$regex, $params] = $this->compilePath($path);

        $this->routes[] = [
            'method' => strtoupper($method),
            'path' => $path,
            'regex' => $regex,
            'params' => $params,
            'handler' => ['class' => $class, 'action' => $action],
            'name' => $name,
        ];
    }

    public function match(string $method, string $uri): ?array
    {
        $method = strtoupper($method);

        foreach ($this->routes as $route) {
            if ($route['method'] !== $method) {
                continue;
            }

            if (preg_match($route['regex'], $uri, $matches)) {
                $values = [];
                foreach ($route['params'] as $param) {
                    $values[$param] = $matches[$param] ?? null;
                }

                return array_merge($route, ['values' => $values]);
            }
        }

        return null;
    }

    /**
     * Convert a path like /users/{id}/posts/{slug}
     * into a named-capture regex and a list of param names.
     *
     * @return array{string, string[]}
     */
    private function compilePath(string $path): array
    {
        $params = [];

        $regex = preg_replace_callback(
            '/\{(\w+)\}/',
            function (array $m) use (&$params): string {
                $params[] = $m[1];
                return '(?P<' . $m[1] . '>[^/]+)';
            },
            $path
        );

        // Anchor and allow optional trailing slash
        $regex = '#^' . $regex . '\/?$#';

        return [$regex, $params];
    }
}

