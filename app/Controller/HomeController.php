<?php

declare(strict_types=1);

namespace Josix\Controller;

use Josix\Core\Routing\Route;
use Twig\Environment;

class HomeController
{
    public function __construct(
        private readonly Environment $twig,
    ) {
    }

    #[Route(path: '/', method: 'GET', name: 'home')]
    public function index(): void
    {
        echo $this->twig->render('home.html.twig', [
            'title' => 'Welcome to Josix',
        ]);
    }
}

