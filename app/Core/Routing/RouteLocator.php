<?php

namespace Josix\Core\Routing;

use Josix\Core\Routing\RouteCollection;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use ReflectionClass;
use ReflectionMethod;

class RouteLocator
{
    public function autoDiscover(RouteCollection $collection): void
    {
        // Auto discover controllers
        $controller_dir = BASE_PATH . "/app/Controller";
        $namespace = "Josix\\Controller\\";

        if (!is_dir($controller_dir)) {
            return;
        }

        $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($controller_dir));

        foreach ($files as $file) {
            if ($file->getExtension() !== 'php') {
                continue;
            }

            // Ensure the controller file is loaded even if Composer autoload
            // is not configured for the example app (e.g. example/*).
            require_once $file->getPathname();

            $className = $namespace . str_replace('.php', '', $file->getFilename());
            $reflector = new ReflectionClass($className);

            foreach ($reflector->getMethods(ReflectionMethod::IS_PUBLIC) as $method) {
                $attributes = $method->getAttributes(Route::class);

                foreach($attributes as $attribute) {
                    $route = $attribute->newInstance();

                    $collection->add(
                        $route->method,
                        $route->path,
                        $className,
                        $method->getName(),
                        $route->name
                    );
                }
            }
     
        }
    }


    private function registerLocatedRoutes(RouteCollection $collection): void
    {
        
    }
}

