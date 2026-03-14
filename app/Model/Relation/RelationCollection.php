<?php

namespace Josix\Model\Relation;

use ArrayAccess;
use ArrayIterator;
use IteratorAggregate;
use Traversable;

class RelationCollection implements IteratorAggregate, ArrayAccess
{
    /** @var Relation[] $relations */
    private array $relations;

    /**
     * @param Relation[] $relations
     */
    public function __construct(array $relations)
    {
        $this->relations = $relations;
    }

    public function getIterator(): Traversable
    {
        return new ArrayIterator($this->relations);
    }

    public function offsetExists(mixed $offset): bool
    {
        return isset($this->relations[$offset]);
    }

    public function offsetGet(mixed $offset): mixed
    {
        return $this->relations[$offset] ?? null;
    }

    public function offsetSet(mixed $offset, mixed $value): void
    {
        if ($offset === null) {
            $this->relations[] = $value;
        } else {
            $this->relations[$offset] = $value;
        }
    }

    public function offsetUnset(mixed $offset): void
    {
        unset($this->relations[$offset]);
    }
}
