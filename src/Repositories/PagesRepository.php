<?php

namespace Hexlet\Code\Repositories;

use Carbon\Carbon;

class PagesRepository
{
    private $conn;

    public function __construct(\PDO $conn)
    {
        $this->conn = $conn;
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

    public function save(string $name, $statusCode = ''): int
    {
        $sql = 'INSERT INTO urls (name, status_code, created_at)
                VALUES (:name, :status_code, :created_at) RETURNING id';
        $stmt = $this->conn->prepare($sql);
        $date = Carbon::now();
        $stmt->execute([
            'name' => $name,
            'status_code' => $statusCode,
            'created_at' => $date
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
}
