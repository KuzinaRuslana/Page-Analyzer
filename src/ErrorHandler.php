<?php

namespace Hexlet\Code;

use Slim\App;
use DI\Container;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;

class ErrorHandler
{
    public static function init(App $app): void
    {
        /** @var Container $container */
        $container = $app->getContainer();

        $app->map(
            ['GET', 'POST', 'PUT', 'DELETE'],
            '/{routes:.+}',
            function (Request $request, Response $response) use ($container) {
                return $container->get('renderer')->render(
                    $response->withStatus(404),
                    'error404.phtml'
                );
            }
        );
    }
}
