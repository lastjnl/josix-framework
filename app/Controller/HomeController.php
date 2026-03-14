<?php

declare(strict_types=1);

namespace Josix\Controller;

use Josix\Core\Http\Response;
use Josix\Core\Routing\Route;

class HomeController extends Controller
{
    #[Route(path: '/', method: 'GET', name: 'home')]
    public function index(): Response
    {
        return new Response(
            $this->render('home.html.twig', ['title' => 'Welcome to Josix'])
        );
    }
}
