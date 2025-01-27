<?php

namespace Hexlet\Code\Tests;

use PHPUnit\Framework\TestCase;
use Slim\Factory\AppFactory;
use Slim\Views\PhpRenderer;
use Slim\Psr7\Factory\ServerRequestFactory;
use DI\Container;

class ErrorHandlerTest extends TestCase
{
    private $app;
    private $rendererMock;

    public function setUp(): void
    {
        $this->rendererMock = $this->createMock(PhpRenderer::class);

        $container = new Container();
        $container->set('renderer', $this->rendererMock);

        AppFactory::setContainer($container);
        $this->app = AppFactory::create();

        $errorHandler = require __DIR__ . '/../src/ErrorHandler.php';
        $errorHandler($this->app);
    }

    public function testRender404()
    {
        $this->rendererMock->expects($this->once())
            ->method('render')
            ->with(
                $this->callback(function ($response) {
                    return $response->getStatusCode() === 404;
                }),
                'error404.phtml'
            )
            ->willReturnCallback(function ($response) {
                return $response;
            });

        $request = (new ServerRequestFactory())->createServerRequest('GET', '/some-non-existent-route');
        $this->app->handle($request);
    }
}
