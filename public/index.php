<?php

require __DIR__ . '/../vendor/autoload.php';

use DI\Container;
use Dotenv\Dotenv;
use Slim\Factory\AppFactory;
use Slim\Flash\Messages;
use Slim\Views\PhpRenderer;

if (file_exists(__DIR__ . '/../.env')) {
    $dotenv = Dotenv::createImmutable(__DIR__ . '/../');
    $dotenv->safeload();
}

session_start();

$container = new Container();

$container->set('renderer', function (): PhpRenderer {
    return new PhpRenderer(__DIR__ . '/../templates');
});

$container->set('flash', function (): Messages {
    return new Messages();
});

$container->set(\PDO::class, function (): PDO {
    $databaseUrl = $_ENV['DATABASE_URL'] ?? null;

    $parsedUrl = parse_url($databaseUrl);
    $host = $parsedUrl['host'];
    $port = $parsedUrl['port'] ?? '5432';
    $dbname = ltrim($parsedUrl['path'], '/');
    $username = $parsedUrl['user'];
    $password = $parsedUrl['pass'];

    $dsn = "pgsql:host=$host;port=$port;dbname=$dbname";

    $conn = new \PDO($dsn, $username, $password);
    $conn->setAttribute(\PDO::ATTR_DEFAULT_FETCH_MODE, \PDO::FETCH_ASSOC);

    return $conn;
});

AppFactory::setContainer($container);
$app = AppFactory::create();
$app->addErrorMiddleware(true, true, true);

$handlers = require __DIR__ . '/../src/handlers.php';
$handlers($app);

$app->run();
