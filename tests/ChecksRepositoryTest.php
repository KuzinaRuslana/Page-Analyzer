<?php

namespace Hexlet\Code\Tests;

use PHPUnit\Framework\TestCase;
use Hexlet\Code\Repositories\ChecksRepository;

class ChecksRepositoryTest extends TestCase
{
    public function testAddCheck(): void
    {
        $pdoStub = $this->createStub(\PDO::class);

        $statementStub = $this->createStub(\PDOStatement::class);
        $statementStub->method('execute')->willReturn(true);

        $pdoStub->method('prepare')->willReturn($statementStub);

        $repository = new ChecksRepository($pdoStub);

        $repository->addCheck(
            1,
            200,
            'Some H1',
            'The best title in the world',
            'This one is the best of the best'
        );

        $this->assertTrue(true);
    }

    public function testGetChecks(): void
    {
        $pdoStub = $this->createStub(\PDO::class);

        $statementStub = $this->createStub(\PDOStatement::class);
        $statementStub->method('fetchAll')->willReturn([
            [
                'url_id' => 1,
                'status_code' => 200,
                'h1' => 'Some H1',
                'title' => 'The best title in the world',
                'description' => 'This one is the best of the best',
                'created_at' => '2025-01-26 20:00:00',
            ],
        ]);

        $pdoStub->method('prepare')->willReturn($statementStub);

        $repository = new ChecksRepository($pdoStub);

        $checks = $repository->getChecks(1);

        $this->assertCount(1, $checks);
        $this->assertSame(200, $checks[0]['status_code']);
        $this->assertSame('Some H1', $checks[0]['h1']);
    }

    public function testGetLastCheckData(): void
    {
        $pdoStub = $this->createStub(\PDO::class);

        $statementStub = $this->createStub(\PDOStatement::class);
        $statementStub->method('fetch')->willReturn([
            'status_code' => 200,
            'created_at' => '2025-01-26 20:00:00',
        ]);

        $pdoStub->method('prepare')->willReturn($statementStub);

        $repository = new ChecksRepository($pdoStub);

        $lastCheck = $repository->getLastCheckData(1);

        $this->assertNotNull($lastCheck);
        $this->assertSame(200, $lastCheck['status_code']);
        $this->assertSame('2025-01-26 20:00:00', $lastCheck['created_at']);
    }
}
