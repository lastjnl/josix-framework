<?php

namespace Tests\Unit\Controller;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Twig\Environment;

/**
 * @template T of object
 */
abstract class AbstractControllerTestCase extends TestCase
{
    protected Environment&MockObject $twig;

    /** @var T $controller */
    protected object $controller;

    protected function setUp(): void
    {
        $this->twig = $this->createMock(Environment::class);
        $this->controller = $this->createController();
        $this->controller::init($this->twig);
    }

    /**
     * @return T
     */
    abstract protected function createController(): object;
}
