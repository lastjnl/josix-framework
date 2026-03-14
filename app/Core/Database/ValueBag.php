<?php

namespace Josix\Core\Database;

class ValueBag
{
    /**
     * Set values for ValueBag
     * @param array<string, mixed> $values
     */
    public function __construct(private array $values) {}

    /** 
     * @return array<string, string> 
     */
    public function getValues(): array
    {
        return $this->values;
    }
}
