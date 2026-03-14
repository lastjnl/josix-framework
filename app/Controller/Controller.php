<?php

namespace Josix\Controller;

use Twig\Environment;

abstract class Controller
{
    private static ?Environment $twig = null;

    public static function init(Environment $twig): void
    {
        self::$twig = $twig;
    }

    protected function render(string $template, array $data = []): string
    {
        return self::$twig->render($template, $data);
    }
}
