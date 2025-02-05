<?php

require __DIR__ . '/../vendor/autoload.php';

use DI\Container;
use Dotenv\Dotenv;
use Slim\Factory\AppFactory;
use Slim\Flash\Messages;
use Slim\Views\PhpRenderer;
use Hexlet\Code\Connection;
use Hexlet\Code\Router;
use Hexlet\Code\ErrorHandler;

$dotenv = Dotenv::createImmutable(__DIR__ . '/../');
$dotenv->safeload();
$dotenv->required(['DATABASE_URL'])->notEmpty();

session_start();

$container = new Container();

$container->set('renderer', function () {
    return new PhpRenderer(__DIR__ . '/../templates');
});

$container->set('flash', function () {
    return new Messages();
});

$container->set(\PDO::class, function () {
    return Connection::get();
});

$app = AppFactory::createFromContainer($container);
$app->addErrorMiddleware(true, true, true);

Router::init($app);

ErrorHandler::init($app);

$app->run();
