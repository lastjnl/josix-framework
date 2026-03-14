<?php

namespace Josix\Core\Env;

use Exception;

class EnvLoader
{
    public function load(string $fileName): void
    {
        if (file_exists($fileName) === false) {
            throw new Exception("Environment file not found: $fileName");
        }

        $lines = file($fileName, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach ($lines as $line) {

            // Skip lines without '='
            if (str_contains($line, '=') === false) {
                continue;
            }

            [$name, $value] = explode('=', $line, 2);
            $name = trim($name);
            $value = trim($value);

            if (array_key_exists($name, $_SERVER) === false && array_key_exists($name, $_ENV) === false) {
                putenv(sprintf('%s=%s', $name, $value));
                $_ENV[$name] = $value;
                $_SERVER[$name] = $value;
            }
        }
    }
}
