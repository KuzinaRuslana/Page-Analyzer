<?php

use Hexlet\Code\Repositories\PagesRepository;
use Hexlet\Code\Repositories\ChecksRepository;
use Hexlet\Code\UrlValidator;
use GuzzleHttp\Client;
use DiDom\Document;

return function ($app) {
    $router = $app->getRouteCollector()->getRouteParser();

    $app->get('/', function ($request, $response) {
        return $this->get('renderer')->render($response, 'index.phtml');
    })->setName('home');

    $app->get('/urls', function ($request, $response) {
        $pagesRepo = new PagesRepository($this->get(\PDO::class));
        $checksRepo = new ChecksRepository($this->get(\PDO::class));
        $urls = $pagesRepo->findAll();

        $urlsWithLastChecks = array_map(function ($url) use ($checksRepo) {
            $lastCheck = $checksRepo->getLastCheckData($url['id']);
            $url['data'] = [
                'last_check' => $lastCheck['created_at'] ?? '',
                'status_code' => $lastCheck['status_code'] ?? ''
            ];
            return $url;
        }, $urls);

        $params = ['urls' => $urlsWithLastChecks];
        return $this->get('renderer')->render($response, 'urls.phtml', $params);
    })->setName('urls');

    $app->post('/urls', function ($request, $response) use ($router) {
        $pagesRepo = new PagesRepository($this->get(\PDO::class));
        $urlData = $request->getParsedBodyParam('url');

        $validator = new UrlValidator();
        $errors = $validator->validateUrl($urlData);

        if (count($errors) > 0) {
            $params = [
                'errors' => $errors,
                'url' => $urlData
            ];
            $response = $response->withStatus(422);
            return $this->get('renderer')->render($response, 'index.phtml', $params);
        }

        $parsedUrl = parse_url($urlData['name']);
        $normalizedUrl = strtolower("{$parsedUrl['scheme']}://{$parsedUrl['host']}");
        $existingPage = $pagesRepo->findByName($normalizedUrl);

        if ($existingPage) {
            $this->get('flash')->addMessage('info', 'Страница уже существует');
            $params = ['id' => $existingPage['id']];
            return $response->withRedirect($router->urlFor('url', $params));
        }

        $newPageId = $pagesRepo->save($normalizedUrl);
        $this->get('flash')->addMessage('success', 'Страница успешно добавлена');
        $params = ['id' => $newPageId];
        return $response->withRedirect($router->urlFor('url', $params));
    });

    $app->get('/urls/{id}', function ($request, $response, $args) {
        $pagesRepo = new PagesRepository($this->get(\PDO::class));
        $checksRepo = new ChecksRepository($this->get(\PDO::class));

        $id = $args['id'];
        $page = $pagesRepo->findById($id);
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
        $pagesRepo = new PagesRepository($this->get(\PDO::class));
        $checksRepo = new ChecksRepository($this->get(\PDO::class));
        $client = new Client();
        $url = $pagesRepo->findById($urlId);

        try {
            $urlName = $client->get($url['name']);
            $statusCode = $urlName->getStatusCode();
            $body = (string) $urlName->getBody();

            $document = new Document($body);
            $h1 = optional($document->first('h1'))->text();
            $title = optional($document->first('title'))->text();
            $description = optional($document->first('meta[name=description]'))->getAttribute('content') ?? null;
            $checksRepo->addCheck($urlId, $statusCode, $h1, $title, $description);
            $this->get('flash')->addMessage('success', 'Страница успешно проверена');
        } catch (\Exception $e) {
            $this->get('flash')->addMessage('error', 'Произошла ошибка при проверке, не удалось подключиться');
        }

        $params = ['id' => $urlId];
        return $response->withRedirect($router->urlFor('url', $params));
    })->setName('url_check');
};
