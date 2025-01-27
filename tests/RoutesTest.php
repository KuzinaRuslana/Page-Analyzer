<?php

namespace Hexlet\Code\Tests;

use PHPUnit\Framework\TestCase;
use Slim\App;
use Slim\Factory\AppFactory;
use Slim\Flash\Messages;
use Slim\Psr7\Factory\ServerRequestFactory;
use Slim\Views\PhpRenderer;
use DI\Container;
use Hexlet\Code\Repositories\PagesRepository;
use Hexlet\Code\Repositories\ChecksRepository;

class RoutesTest extends TestCase
{
    private App $app;
    private $pagesRepoMock;
    private $checksRepoMock;

    protected function setUp(): void
    {
        $container = new Container();

        $pdoStatementMock = $this->createMock(\PDOStatement::class);
        $pdoStatementMock->method('execute')->willReturn(true);
        $pdoStatementMock->method('fetchAll')
            ->willReturn([
                ['id' => 1, 'name' => 'https://some-pretty-url.com', 'created_at' => '2025-01-26'],
                ['id' => 2, 'name' => 'https://some-great-url.com', 'created_at' => '2025-01-27'],
            ]);
        $pdoStatementMock->method('fetch')
            ->willReturn(['status_code' => 200, 'created_at' => '2025-01-28']);
        $pdoStatementMock->method('fetchColumn')->willReturn(1);

        $pdoMock = $this->createMock(\PDO::class);
        $pdoMock->method('query')->willReturn($pdoStatementMock);
        $pdoMock->method('prepare')->willReturn($pdoStatementMock);

        $container->set(\PDO::class, fn() => $pdoMock);
        $container->set('renderer', fn() => new PhpRenderer(__DIR__ . '/../templates'));
        $container->set('flash', fn() => new Messages());

        AppFactory::setContainer($container);
        $this->app = AppFactory::create();

        $routes = require __DIR__ . '/../src/Routes.php';
        $routes($this->app);
    }

    public function testHomePage(): void
    {
        $request = (new ServerRequestFactory())->createServerRequest('GET', '/');
        $response = $this->app->handle($request);

        $this->assertEquals(200, $response->getStatusCode());
        $body = (string) $response->getBody();
        $this->assertStringContainsString('Бесплатно проверяйте сайты на SEO пригодность', $body);
    }

    public function testUrlsRoute(): void
    {
        $this->pagesRepoMock = $this->createMock(PagesRepository::class);
        $this->pagesRepoMock->method('findAll')
            ->willReturn([
                ['id' => 1, 'name' => 'https://some-pretty-url.com', 'created_at' => '2025-01-26'],
                ['id' => 2, 'name' => 'https://some-great-url.com', 'created_at' => '2025-01-27'],
            ]);

        $this->checksRepoMock = $this->createMock(ChecksRepository::class);
        $this->checksRepoMock->method('getLastCheckData')
            ->willReturnOnConsecutiveCalls(
                ['created_at' => '2025-01-28', 'status_code' => 200],
                ['created_at' => '2025-01-29', 'status_code' => 404]
            );

        $container = new Container();
        $container->set(PagesRepository::class, fn() => $this->pagesRepoMock);
        $container->set(ChecksRepository::class, fn() => $this->checksRepoMock);

        $request = (new ServerRequestFactory())
            ->createServerRequest('GET', '/urls');

        $response = $this->app->handle($request);

        $this->assertEquals(200, $response->getStatusCode());
        $body = (string) $response->getBody();
        $this->assertStringContainsString('https://some-pretty-url.com', $body);
        $this->assertStringContainsString('2025-01-28', $body);
        $this->assertStringContainsString('200', $body);
    }
}
