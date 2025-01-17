<?php

namespace Hexlet\Code\Repositories;

use Carbon\Carbon;

class ChecksRepository
{
    private \PDO $conn;

    public function __construct(\PDO $conn)
    {
        $this->conn = $conn;
    }

    public function addCheck(int $urlId): void
    {
        $sql = 'INSERT INTO url_checks (url_id, created_at) VALUES (:url_id, :created_at)';
        $stmt = $this->conn->prepare($sql);
        $date = Carbon::now();
        $stmt->execute([
            'url_id' => $urlId,
            'created_at' => $date,
        ]);
    }

    public function getChecks(int $urlId): array
    {
        $sql = 'SELECT * FROM url_checks WHERE url_id = :url_id ORDER BY created_at DESC';
        $stmt = $this->conn->prepare($sql);
        $stmt->execute(['url_id' => $urlId]);
        return $stmt->fetchAll();
    }

    public function findByUrlId(int $urlId): array
    {
        $sql = 'SELECT * FROM url_checks WHERE url_id = :url_id ORDER BY created_at DESC';
        $stmt = $this->conn->prepare($sql);
        $stmt->execute(['url_id' => $urlId]);
        return $stmt->fetchAll();
    }

    public function getLastCheckDate(int $urlId): ?string
    {
        $sql = 'SELECT created_at FROM url_checks WHERE url_id = :url_id ORDER BY created_at DESC LIMIT 1';
        $stmt = $this->conn->prepare($sql);
        $stmt->execute(['url_id' => $urlId]);
        return $stmt->fetchColumn() ?: null;
    }
}
