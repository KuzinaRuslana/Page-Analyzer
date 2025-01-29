<?php

require __DIR__ . '/../vendor/autoload.php';

use DI\Container;
use Dotenv\Dotenv;
use Slim\Factory\AppFactory;
use Slim\Flash\Messages;
use Slim\Views\PhpRenderer;

// $dotenv = Dotenv::createImmutable(__DIR__ . '/../');
// $dotenv->safeload();
// $dotenv->required(['DATABASE_URL'])->notEmpty();

// session_start();

$container = new Container();

$container->set('renderer', function () {
    return new PhpRenderer(__DIR__ . '/../templates');
});

// $container->set('flash', function () {
//     return new Messages();
// });

// $container->set(\PDO::class, function () {
//     $databaseUrl = $_ENV['DATABASE_URL'] ?? null;

//     $parsedUrl = parse_url($databaseUrl);
//     $host = $parsedUrl['host'];
//     $port = $parsedUrl['port'] ?? '5432';
//     $dbname = ltrim($parsedUrl['path'], '/');
//     $username = $parsedUrl['user'];
//     $password = $parsedUrl['pass'];

//     $dsn = "pgsql:host=$host;port=$port;dbname=$dbname";

//     $conn = new \PDO($dsn, $username, $password);
//     $conn->setAttribute(\PDO::ATTR_DEFAULT_FETCH_MODE, \PDO::FETCH_ASSOC);

//     return $conn;
// });

$app = AppFactory::createFromContainer($container);
// $app->addErrorMiddleware(true, true, true);

$app->get('/', function ($request, $response) {
    return $this->get('renderer')->render($response, 'index.phtml');
})->setName('home');

// $routes = require __DIR__ . '/../src/Routes.php';
// $routes($app);

// $errorHandler = require __DIR__ . '/../src/ErrorHandler.php';
// $errorHandler($app);

$app->run();
