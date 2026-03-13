<?php

namespace Josix\Model\Relation;

class Join
{
    public function __construct(public string $join, public string $selectValue)
    {
    }
}

