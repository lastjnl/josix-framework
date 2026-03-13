<?php

namespace Josix\Core\Injection;

use Exception;
use Josix\Core\Exception\ServiceNotFoundException;
use ReflectionClass;
use ReflectionNamedType;

class Container
{
    private array $instances = [];
    
    public function __construct(private array $config = [])
    {
    }

    public function has(string $id): bool
    {
        return array_key_exists($id, $this->config) || class_exists($id);
    }

    public function get(string $id): mixed
    {
        if (!$this->has($id)) {
            throw new ServiceNotFoundException($id);
        }

        $this->instances[$id] ??= $this->instantiateService($id);
        return $this->instances[$id];
    }

    private function instantiateService(string $id): mixed
    {
        $config = $this->config[$id] ?? null;

        // Existing: factory callable
        if (is_callable($config)) {
            return call_user_func($config);
        }

        // Existing: alias string
        if (is_string($config)) {
            return $this->get($config);
        }

        // No config, try to autowire
        if ($config === null) {
            return $this->autowire($id);
        }

        // Existing: explicit dependency array
        $args = array_map(
            fn($dep) => is_callable($dep) ? call_user_func($dep) : $this->get($dep),
            $config
        );

        return new $id(...$args);
    }

    private function autowire(string $id)
    {
        $reflector = new ReflectionClass($id);

        if (!$reflector->isInstantiable()) {
            throw new Exception("Cannot autowire $id: not instantiable.");
        }

        $constructor = $reflector->getConstructor();

        // No constructor = no dependencies, just create it
        if($constructor === null) {
            return new $id();
        }

        $args = [];
        foreach ($constructor->getParameters() as $param) {
            $type = $param->getType();

            if ($type instanceof ReflectionNamedType && !$type->isBuiltin()) {
                // It's a class/interface type -> resolve from container
                $args[$param->getName()] = $this->get($type->getName());
            } elseif ($param->isDefaultValueAvailable()) {
                // Scalar with a default -> use the default
                $args[$param->getName()] = $param->getDefaultValue();
            } else {
                throw new Exception(
                    "Cannot autowire parameter \${$param->getName()} of $id: " .
                    "it's a scalar with no default. Add it to the config manually."
                );
            }
        }

        return new $id(...$args);
    }
}

