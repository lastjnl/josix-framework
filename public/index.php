<?php

declare(strict_types=1);

use Josix\Core\Env\EnvLoader;
use Josix\Core\Injection\Container;
use Josix\Core\Routing\RouteCollection;
use Josix\Core\Routing\RouteLocator;
use Josix\Core\Routing\Router;
use Twig\Environment;
use Twig\Loader\FilesystemLoader;

require __DIR__ . '/../vendor/autoload.php';

define('BASE_PATH', dirname(__DIR__));

// Load environment variables if a .env file exists.
$envFile = BASE_PATH . '/.env';
if (file_exists($envFile)) {
    (new EnvLoader())->load($envFile);
}

// Resolve relative DB_PATH against BASE_PATH
$dbPath = getenv('DB_PATH') ?: ':memory:';
if ($dbPath !== ':memory:' && !str_starts_with($dbPath, '/')) {
    $dbPath = BASE_PATH . '/' . $dbPath;

    // Ensure the directory exists
    $dbDir = dirname($dbPath);
    if (!is_dir($dbDir)) {
        mkdir($dbDir, 0755, true);
    }

    putenv("DB_PATH=$dbPath");
    $_ENV['DB_PATH'] = $dbPath;
}

$container = new Container([
    Environment::class => function () {
        $loader = new FilesystemLoader(BASE_PATH . '/templates');

        return new Environment($loader, [
            'cache' => false,
        ]);
    },
]);

$routes    = new RouteCollection();
$locator   = new RouteLocator();
$router    = new Router($locator, $routes, $container);

$router->dispatch();

