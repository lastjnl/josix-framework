<?php

namespace Josix\Core\Env;

class Env
{
    public static function getString(string $name): ?string
    {
        return getenv($name);
    }
}
