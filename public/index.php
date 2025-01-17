<?php

require __DIR__ . '/../vendor/autoload.php';

use DI\Container;
use Dotenv\Dotenv;
use Slim\Factory\AppFactory;
use Slim\Flash\Messages;
use Slim\Middleware\MethodOverrideMiddleware;
use Slim\Views\PhpRenderer;
use Hexlet\Code\Repositories\PagesRepository;
use Hexlet\Code\Repositories\ChecksRepository;
use Hexlet\Code\Validator;

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

$router = $app->getRouteCollector()->getRouteParser();

$app->get('/', function ($request, $response) {
    return $this->get('renderer')->render($response, 'index.phtml');
})->setName('home');

$app->get('/urls', function ($request, $response) {
    $repo = new PagesRepository($this->get(\PDO::class));
    $checksRepo = new ChecksRepository($this->get(\PDO::class));
    $urls = $repo->findAll();

    foreach ($urls as &$url) {
        $url['last_check'] = $checksRepo->getLastCheckDate($url['id']);
    }

    $params = ['urls' => $urls];
    return $this->get('renderer')->render($response, 'urls.phtml', $params);
})->setName('urls');

$app->post('/urls', function ($request, $response) use ($router) {
    $repo = new PagesRepository($this->get(\PDO::class));
    $UrlData = $request->getParsedBodyParam('url');

    $validator = new Validator();
    $errors = $validator->validateUrl($UrlData);

    if (count($errors) > 0) {
        $params = [
            'errors' => $errors,
            'url' => $UrlData
        ];
        $response = $response->withStatus(422);
        return $this->get('renderer')->render($response, 'index.phtml', $params);
    }

    $parsedUrl = parse_url($UrlData['name']);
    $normalizedUrl = strtolower("{$parsedUrl['scheme']}://{$parsedUrl['host']}");

    $existingPage = $repo->findByName($normalizedUrl);
    if ($existingPage) {
        $this->get('flash')->addMessage('info', 'Страница уже существует');
        $params = ['id' => $existingPage['id']];
        return $response->withRedirect($router->urlFor('url', $params));
    }

    $newPageId = $repo->save($normalizedUrl);
    $this->get('flash')->addMessage('success', 'URL успешно добавлен');
    $params = ['id' => $newPageId];
    return $response->withRedirect($router->urlFor('url', $params));
});

$app->get('/urls/{id}', function ($request, $response, $args) {
    $repo = new PagesRepository($this->get(\PDO::class));
    $id = $args['id'];
    $page = $repo->find($id);
    $checksRepo = new ChecksRepository($this->get(\PDO::class));

    if (!$page) {
        return $response->withStatus(404)->write('Page not found');
    }

    $flash = $this->get('flash')->getMessages();

    $params = [
        'page' => $page,
        'checks' => $checksRepo->getChecks($args['id']),
        'flash' => $flash
    ];

    return $this->get('renderer')->render($response, 'url.phtml', $params);
})->setName('url');

$app->post('/urls/{url_id}/checks', function ($request, $response, $args) use ($router) {
    $urlId = (int) $args['url_id'];

    $checksRepo = new ChecksRepository($this->get(\PDO::class));
    $checksRepo->addCheck($urlId);
    $params = ['id' => $urlId];
    return $response->withRedirect($router->urlFor('url', $params));
})->setName('url_check');

$app->run();
