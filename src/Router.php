<?php

namespace Hexlet\Code;

use Slim\App;
use Slim\Exception\HttpNotFoundException;
use DI\Container;
use Hexlet\Code\Repositories\PagesRepository;
use Hexlet\Code\Repositories\ChecksRepository;
use Hexlet\Code\UrlValidator;
use GuzzleHttp\Client;
use DiDom\Document;

class Router
{
    /**
     * @param App<Container> $app
     */
    public static function init(App $app): void
    {
        /** @var Container $container */
        $container = $app->getContainer();
        $router = $app->getRouteCollector()->getRouteParser();

        $app->get('/', function ($request, $response) use ($container) {
            return $container->get('renderer')->render($response, 'index.phtml');
        })->setName('home');

        $app->get('/urls', function ($request, $response) use ($container) {
            $pagesRepo = new PagesRepository($container->get(\PDO::class));
            $checksRepo = new ChecksRepository($container->get(\PDO::class));
            $urls = $pagesRepo->findAll();

            $urlsWithLastChecks = array_map(function ($url) use ($checksRepo) {
                $lastCheck = $checksRepo->getLastCheckData($url['id']);
                $url['data'] = [
                    'last_check' => $lastCheck['created_at'] ?? '',
                    'status_code' => $lastCheck['status_code'] ?? ''
                ];
                return $url;
            }, $urls);

            $params = [
                'urls' => $urlsWithLastChecks
            ];
            return $container->get('renderer')->render($response, 'urls.phtml', $params);
        })->setName('urls');

        $app->post('/urls', function ($request, $response) use ($container, $router) {
            $pagesRepo = new PagesRepository($container->get(\PDO::class));
            $urlData = $request->getParsedBodyParam('url');

            $validator = new UrlValidator();
            $errors = $validator->validateUrl($urlData);

            if (count($errors) > 0) {
                $params = [
                    'errors' => $errors,
                    'url' => $urlData
                ];
                $response = $response->withStatus(422);
                return $container->get('renderer')->render($response, 'index.phtml', $params);
            }

            $parsedUrl = parse_url($urlData['name']);
            $normalizedUrl = strtolower("{$parsedUrl['scheme']}://{$parsedUrl['host']}");
            $existingPage = $pagesRepo->findByName($normalizedUrl);

            if ($existingPage) {
                $container->get('flash')->addMessage('info', 'Страница уже существует');
                $params = ['id' => $existingPage['id']];
                return $response->withRedirect($router->urlFor('url', $params));
            }

            $newPageId = $pagesRepo->save($normalizedUrl);
            $container->get('flash')->addMessage('success', 'Страница успешно добавлена');
            $params = ['id' => (string) $newPageId];
            return $response->withRedirect($router->urlFor('url', $params));
        });

        $app->get('/urls/{id}', function ($request, $response, $args) use ($container) {
            $pagesRepo = new PagesRepository($container->get(\PDO::class));
            $checksRepo = new ChecksRepository($container->get(\PDO::class));

            $id = $args['id'];
            $page = $pagesRepo->findById($id);

            if (!$page) {
                throw new HttpNotFoundException($request);
            }

            $flash = $container->get('flash')->getMessages();
            $params = [
                'page' => $page,
                'checks' => $checksRepo->getChecks($args['id']),
                'flash' => $flash
            ];

            return $container->get('renderer')->render($response, 'url.phtml', $params);
        })->setName('url');

        $app->post('/urls/{url_id}/checks', function ($request, $response, $args) use ($container, $router) {
            $urlId = (int) $args['url_id'];
            $pagesRepo = new PagesRepository($container->get(\PDO::class));
            $checksRepo = new ChecksRepository($container->get(\PDO::class));
            $client = new Client();
            $url = $pagesRepo->findById($urlId);

            try {
                $urlName = $client->get($url['name']);
                $statusCode = $urlName->getStatusCode();
                $body = (string) $urlName->getBody();

                $document = new Document($body);
                $h1 = optional($document->first('h1'))->text();
                $title = optional($document->first('title'))->text();
                $descriptionTag = $document->first('meta[name=description]');
                $description = $descriptionTag ? $descriptionTag->getAttribute('content') : null;
                $checksRepo->addCheck($urlId, $statusCode, $h1, $title, $description);
                $container->get('flash')->addMessage('success', 'Страница успешно проверена');
            } catch (\Exception $e) {
                $container->get('flash')->addMessage('error', 'Произошла ошибка при проверке, не удалось подключиться');
            }

            $params = ['id' => (string) $urlId];
            return $response->withRedirect($router->urlFor('url', $params));
        })->setName('url_check');
    }
}
