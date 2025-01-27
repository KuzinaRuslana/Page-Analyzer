<?php

namespace Hexlet\Code\Tests;

use PHPUnit\Framework\TestCase;
use Hexlet\Code\Repositories\PagesRepository;

class PagesRepositoryTest extends TestCase
{
    public function testFindById()
    {
        $pdoStub = $this->createStub(\PDO::class);

        $statementStub = $this->createStub(\PDOStatement::class);
        $statementStub->method('fetch')->willReturn([
            'id' => 1,
            'name' => 'https://some-fine-url.com',
            'created_at' => '2025-01-26 20:00:00'
        ]);

        $pdoStub->method('prepare')->willReturn($statementStub);

        $repository = new PagesRepository($pdoStub);
        $page = $repository->findById(1);

        $this->assertSame(1, $page['id']);
        $this->assertSame('https://some-fine-url.com', $page['name']);
        $this->assertSame('2025-01-26 20:00:00', $page['created_at']);
    }

    public function testFindByName()
    {
        $pdoStub = $this->createStub(\PDO::class);

        $statementStub = $this->createStub(\PDOStatement::class);
        $statementStub->method('fetch')->willReturn([
            'id' => 1,
            'name' => 'https://some-fine-url.com',
            'created_at' => '2025-01-26 20:00:00'
        ]);

        $pdoStub->method('prepare')->willReturn($statementStub);

        $repository = new PagesRepository($pdoStub);
        $page = $repository->findByName('https://some-fine-url.com');

        $this->assertNotNull($page);
        $this->assertSame(1, $page['id']);
        $this->assertSame('https://some-fine-url.com', $page['name']);
    }

    public function testFindAll()
    {
        $pdoStub = $this->createStub(\PDO::class);

        $statementStub = $this->createStub(\PDOStatement::class);
        $statementStub->method('fetchAll')->willReturn([
            ['id' => 1, 'name' => 'https://some-fine-url.com', 'created_at' => '2025-01-26 20:00:00'],
            ['id' => 2, 'name' => 'https://some-nice-url.com', 'created_at' => '2025-02-01 17:00:00']
        ]);

        $pdoStub->method('query')->willReturn($statementStub);

        $repository = new PagesRepository($pdoStub);
        $pages = $repository->findAll();

        $this->assertCount(2, $pages);
        $this->assertSame('https://some-fine-url.com', $pages[0]['name']);
        $this->assertSame('https://some-nice-url.com', $pages[1]['name']);
    }

    public function testSave()
    {
        $pdoStub = $this->createStub(\PDO::class);

        $statementStub = $this->createStub(\PDOStatement::class);
        $statementStub->method('execute')->willReturn(true);
        $statementStub->method('fetchColumn')->willReturn(1);

        $pdoStub->method('prepare')->willReturn($statementStub);

        $repository = new PagesRepository($pdoStub);
        $result = $repository->save('https://some-fine-url.com');

        $this->assertIsInt($result);
        $this->assertSame(1, $result);
    }
}
