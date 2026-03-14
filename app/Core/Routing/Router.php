<?php

namespace Josix\Core\Routing;

use Exception;
use Josix\Core\Http\Response;
use Josix\Core\Routing\RouteCollection;
use Josix\Core\Routing\RouteLocator;
use ReflectionMethod;
use Josix\Core\Injection\Container;

class Router
{
    public function __construct(
        private readonly RouteLocator $locator,
        private readonly RouteCollection $routes,
        private readonly Container $container
    ) {
        $this->locator->autoDiscover($this->routes);
    }

    public function dispatch(): Response
    {
        $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
        $uri = $this->parseUri();

        $matched = $this->routes->match($method, $uri);

        if ($matched === null) {
            echo '404';
            exit();
        }

        return $this->callHandler($matched);

    }

    private function callHandler(array $route): Response
    {
        ['class' => $class, 'action' => $action] = $route['handler'];
        $values = $route['values'];

        if (!class_exists($class)) {
            throw new Exception("Controller class [{$class}] not found.");
        }

        $controller = $this->container->get($class);

        if (!method_exists($controller, $action)) {
            throw new Exception("Action [{$action}] not found on [{$class}].");
        }

        // Inject route params in the order the method declares them
        $reflector  = new ReflectionMethod($controller, $action);
        $args       = [];

        foreach ($reflector->getParameters() as $param) {
            $name  = $param->getName();
            $value = $values[$name] ?? null;

            // Cast to declared type when possible
            if ($value !== null && $param->hasType()) {
                $type = $param->getType();
                settype($value, $type === 'int' ? 'integer' : ($type === 'float' ? 'double' : 'string'));
            }

            $args[] = $value;
        }

        return $controller->$action(...$args);
    }

    private function parseUri(): string
    {
        $uri = $_SERVER['REQUEST_URI'] ?? '/';

        // Strip query string
        if (false !== $pos = strpos($uri, '?')) {
            $uri = substr($uri, 0, $pos);
        }

        return '/' . trim($uri, '/');
    }
}
