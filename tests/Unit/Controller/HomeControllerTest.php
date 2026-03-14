<?php

namespace Tests\Unit\Controller;

use Josix\Controller\HomeController;
use Josix\Core\Http\Response;
use Tests\Unit\Controller\AbstractControllerTestCase;

/**
 * @extends AbstractControllerTestCase<HomeController>
 */
final class HomeControllerTest extends AbstractControllerTestCase
{
    protected function createController(): object
    {
        return new HomeController($this->twig);
    }

    public function testIndexRendersCorrectTemplate(): void
    {
        $this->twig
            ->expects($this->once())
            ->method('render')
            ->with('home.html.twig', ['title' => 'Welcome to Josix'])
            ->willReturn('Rendered Template');

        /** @var Response $result */
        $result = $this->controller->index();

        $this->assertSame('Rendered Template', $result->getContent());
    }
}