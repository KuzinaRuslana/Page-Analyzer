<?php

require __DIR__ . '/../vendor/autoload.php';

use DI\Container;
use Dotenv\Dotenv;
use Slim\Factory\AppFactory;
use Slim\Flash\Messages;
use Slim\Middleware\MethodOverrideMiddleware;
use Slim\Views\PhpRenderer;
use Hexlet\Code\PagesRepository;
use Hexlet\Code\Validator;

if (file_exists(__DIR__ . '/../.env')) {
    $dotenv = Dotenv::createImmutable(__DIR__ . '/../');
    $dotenv->safeload();
}

session_start();
$container = new Container();

$container->set('renderer', function () {
    return new PhpRenderer(__DIR__ . '/../templates');
});

$container->set('flash', function () {
    return new Messages();
});

$container->set(\PDO::class, function () {
    $databaseUrl = $_ENV['DATABASE_URL'] ?? null;
    if (!$databaseUrl) {
        throw new \Exception('DATABASE_URL is not set');
    }

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
$app->add(MethodOverrideMiddleware::class);
$app->addErrorMiddleware(true, true, true);

$router = $app->getRouteCollector()->getRouteParser();

$app->get('/', function ($request, $response) {
    return $this->get('renderer')->render($response, 'index.phtml');
})->setName('home');

$app->get('/urls', function ($request, $response) {
    $repo = new PagesRepository($this->get(\PDO::class));
    $urls = $repo->findAll();

    return $this->get('renderer')->render($response, 'urls.phtml', ['urls' => $urls]);
})->setName('urls');

$app->post('/urls', function ($request, $response) use ($router) {
    $repo = new PagesRepository($this->get(\PDO::class));
    $data = $request->getParsedBodyParam('url');
    $validator = new Validator();
    $errors = $validator->validateUrl($data);

    if (count($errors) > 0) {
        $params = ['errors' => $errors, 'url' => $data];
        $response = $response->withStatus(422);
        return $this->get('renderer')->render($response, 'index.phtml', $params);
    }

    $parsedUrl = parse_url($data['name']);
    $normalizedUrl = strtolower("{$parsedUrl['scheme']}://{$parsedUrl['host']}");

    $existingPage = $repo->findByName($normalizedUrl);
    if ($existingPage) {
        $this->get('flash')->addMessage('info', 'Страница уже существует');
        return $response->withRedirect($router->urlFor('url', ['id' => $existingPage['id']]));
    }

    $newPageId = $repo->save($normalizedUrl);
    $this->get('flash')->addMessage('success', 'URL успешно добавлен');
    return $response->withRedirect($router->urlFor('url', ['id' => $newPageId]));
});

$app->get('/urls/{id}', function ($request, $response, $args) {
    $repo = new PagesRepository($this->get(\PDO::class));
    $page = $repo->find($args['id']);
    $flash = $this->get('flash')->getMessages();
    
    if (!$page) {
        return $response->withStatus(404)->write('Page not found');
    }

    $params = [
        'page' => $page,
        'checks' => $repo->getChecks($args['id']), // Получаем проверки (реализуем ниже)
        'flash' => $flash
    ];

    return $this->get('renderer')->render($response, 'url.phtml', $params);
})->setName('url');

$app->run();
