<?php

use Hexlet\Code\Repositories\PagesRepository;
use Hexlet\Code\Repositories\ChecksRepository;
use Hexlet\Code\Validator;
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

        $existingPage = $pagesRepo->findByName($normalizedUrl);
        if ($existingPage) {
            $this->get('flash')->addMessage('info', 'Страница уже существует');
            $params = ['id' => $existingPage['id']];
            return $response->withRedirect($router->urlFor('url', $params));
        }

        $newPageId = $pagesRepo->save($normalizedUrl);
        $this->get('flash')->addMessage('success', 'URL успешно добавлен');
        $params = ['id' => $newPageId];
        return $response->withRedirect($router->urlFor('url', $params));
    });

    $app->get('/urls/{id}', function ($request, $response, $args) {
        $pagesRepo = new PagesRepository($this->get(\PDO::class));
        $id = $args['id'];
        $page = $pagesRepo->find($id);
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
        $pagesRepo = new PagesRepository($this->get(\PDO::class));
        $checksRepo = new ChecksRepository($this->get(\PDO::class));
        $client = new Client();
        $url = $pagesRepo->find($urlId);

        try {
            $res = $client->get($url['name']);
            $statusCode = $res->getStatusCode();
            $body = (string) $res->getBody();

            $document = new Document($body);
            $h1 = optional($document->first('h1'))->text();
            $title = optional($document->first('title'))->text();
            $description = optional($document->first('meta[name=description]'))->getAttribute('content');
            $checksRepo->addCheck($urlId, $statusCode, $h1, $title, $description);
            $this->get('flash')->addMessage('success', 'Страница успешно проверена');
        } catch (\Exception $e) {
            $this->get('flash')->addMessage('error', 'Произошла ошибка при проверке, не удалось подключиться');
        }

        $params = ['id' => $urlId];
        return $response->withRedirect($router->urlFor('url', $params));
    })->setName('url_check');
};
