<?php

namespace Hexlet\Code;

use Carbon\Carbon;

class PagesRepository
{
    private $conn;

    public function __construct(\PDO $conn)
    {
        $this->conn = $conn;
    }

    public function getAll(): array
    {
        $sql = 'SELECT * FROM urls ORDER BY id DESC';
        $stmt = $this->conn->query($sql);
        return $stmt->fetchAll();
    }

    public function find($id): mixed
    {
        $sql = 'SELECT * FROM urls WHERE id = ?';
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([$id]);
        return $stmt->fetch();
    }

    public function findAll(): array
    {
        $sql = 'SELECT * FROM urls ORDER BY created_at DESC';
        $statement = $this->conn->query($sql);
        return $statement->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function save(string $name): int
{
    $sql = 'INSERT INTO urls (name, created_at) VALUES (:name, :created_at) RETURNING id';
    $stmt = $this->conn->prepare($sql);
    $date = Carbon::now();
    $stmt->execute([
        'name' => $name,
        'created_at' => $date,
    ]);

    $id = $stmt->fetchColumn();
    return (int) $id;
}

    public function findByName(string $name): ?array
    {
        $sql = 'SELECT * FROM urls WHERE name = :name';
        $stmt = $this->conn->prepare($sql);
        $stmt->execute(['name' => $name]);
        $result = $stmt->fetch();
        return $result ?: null;
    }

    public function getChecks(int $urlId): array
    {
        $sql = 'SELECT * FROM url_checks WHERE url_id = :url_id ORDER BY created_at DESC';
        $stmt = $this->conn->prepare($sql);
        $stmt->execute(['url_id' => $urlId]);
        return $stmt->fetchAll();
    }
}
