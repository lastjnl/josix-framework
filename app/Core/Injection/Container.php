<?php

namespace Josix\Core\Injection;

use Exception;
use Josix\Core\Exception\ServiceNotFoundException;
use ReflectionClass;
use ReflectionNamedType;
use Twig\Environment;

class Container
{
    private array $instances = [];

    public function __construct(private array $config = []) {}

    public function has(string $identifier): bool
    {
        return array_key_exists($identifier, $this->config) || class_exists($identifier);
    }

    public function get(string $identifier): mixed
    {
        if (!$this->has($identifier)) {
            throw new ServiceNotFoundException($identifier);
        }

        $this->instances[$identifier] ??= $this->instantiateService($identifier);
        return $this->instances[$identifier];
    }

    private function instantiateService(string $identifier): mixed
    {
        $config = $this->config[$identifier] ?? null;

        // Existing: factory callable
        if (is_callable($config)) {
            return $this->initializeService(call_user_func($config));
        }

        // Existing: alias string
        if (is_string($config)) {
            return $this->get($config);
        }

        // No config, try to autowire
        if ($config === null) {
            return $this->autowire($identifier);
        }

        // Existing: explicit dependency array
        $args = array_map(
            fn($dep) => is_callable($dep) ? call_user_func($dep) : $this->get($dep),
            $config
        );

        return $this->initializeService(new $identifier(...$args));
    }

    private function autowire(string $identifier)
    {
        $reflector = new ReflectionClass($identifier);

        if (!$reflector->isInstantiable()) {
            throw new Exception("Cannot autowire $identifier: not instantiable.");
        }

        $constructor = $reflector->getConstructor();

        // No constructor = no dependencies, just create it
        if ($constructor === null) {
            return $this->initializeService(new $identifier());
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
            }
            
            throw new Exception(
                "Cannot autowire parameter \${$param->getName()} of $identifier: "
                . "it's a scalar with no default. Add it to the config manually."
            );
        }

        return $this->initializeService(new $identifier(...$args));
    }

    private function initializeService(mixed $service): mixed
    {
        if ($service instanceof NeedsTwig) {
            $service->setTwig($this->get(Environment::class));
        }

        return $service;
    }
}
