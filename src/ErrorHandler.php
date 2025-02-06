<?php

namespace Hexlet\Code;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Exception\HttpNotFoundException;
use Slim\Interfaces\ErrorHandlerInterface;
use Slim\Middleware\ErrorMiddleware;
use Slim\Views\PhpRenderer;
use Throwable;

class ErrorHandler implements ErrorHandlerInterface
{
    private PhpRenderer $renderer;

    public function __construct(PhpRenderer $renderer)
    {
        $this->renderer = $renderer;
    }

    public function __invoke(
        Request $request,
        Throwable $exception,
        bool $displayErrorDetails,
        bool $logErrors,
        bool $logErrorDetails
    ): Response {
        $response = new \Slim\Psr7\Response();

        if ($exception instanceof HttpNotFoundException) {
            return $this->renderer->render($response->withStatus(404), 'error404.phtml', [
                'currentRoute' => 'error'
            ]);
        }

        return $this->renderer->render($response->withStatus(500), 'error500.phtml', [
            'currentRoute' => 'error'
        ]);
    }

    public static function register(ErrorMiddleware $errorMiddleware, PhpRenderer $renderer)
    {
        $errorMiddleware->setDefaultErrorHandler(new self($renderer));
    }
}
