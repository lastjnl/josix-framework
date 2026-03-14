<?php

namespace Josix\Model;

use InvalidArgumentException;
use ReflectionClass;
use ReflectionProperty;

abstract class Model
{
    protected array $properties = [];

    public function get($propertyName): mixed
    {
        return $this->properties[$propertyName] ?? null;
    }

    public function __get(string $name): mixed
    {
        return $this->properties[$name] ?? null;
    }

    public function __isset(string $name): bool
    {
        return isset($this->properties[$name]);
    }

    public function set($propertyName, $value): self
    {
        $this->properties[$propertyName] = $value;

        return $this;
    }

    public function __set(string $name, mixed $value): void
    {
        if (is_array($value)) {
            $expectedClass = $this->resolveArrayItemType($name);
            if ($expectedClass !== null) {
                foreach ($value as $index => $item) {
                    if (!$item instanceof $expectedClass) {
                        $actualType = is_object($item) ? get_class($item) : gettype($item);
                        throw new InvalidArgumentException(
                            "Item at index {$index} in '{$name}' must be an instance of {$expectedClass}, got {$actualType}."
                        );
                    }
                }
            }
        } elseif (is_object($value)) {
            $expectedClass = $this->resolveObjectType($name);
            if ($expectedClass !== null && !$value instanceof $expectedClass) {
                $actualType = get_class($value);
                throw new InvalidArgumentException(
                    "Property '{$name}' must be an instance of {$expectedClass}, got {$actualType}."
                );
            }
        }

        $this->$name = $value;
    }

    /**
     * Resolves the expected item class from a @var ClassName[] docblock annotation.
     */
    private function resolveArrayItemType(string $propertyName): ?string
    {
        return $this->resolveDocblockType($propertyName, '/^([A-Za-z_\\\\]+)\[\]$/');
    }

    /**
     * Resolves the expected class from a @var ClassName docblock annotation.
     */
    private function resolveObjectType(string $propertyName): ?string
    {
        return $this->resolveDocblockType($propertyName, '/^([A-Za-z_\\\\]+)$/');
    }

    private function resolveDocblockType(string $propertyName, string $pattern): ?string
    {
        try {
            $reflection = new ReflectionProperty(static::class, $propertyName);
        } catch (\ReflectionException) {
            return null;
        }

        $docComment = $reflection->getDocComment();
        if ($docComment === false) {
            return null;
        }

        if (!preg_match('/@var\s+(\S+)/', $docComment, $matches)) {
            return null;
        }

        $typeHint = $matches[1];
        if (!preg_match($pattern, $typeHint, $typeMatches)) {
            return null;
        }

        $className = $typeMatches[1];

        // Resolve relative class names against the model's namespace
        if (!class_exists($className)) {
            $namespace = (new ReflectionClass(static::class))->getNamespaceName();
            $fqcn = $namespace . '\\' . $className;
            if (class_exists($fqcn)) {
                return $fqcn;
            }
        }

        return class_exists($className) ? $className : null;
    }
}
