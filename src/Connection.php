<?php

namespace Hexlet\Code;

class Connection
{
    private static \PDO $pdo;

    public static function get(): \PDO
    {
        $databaseUrl = $_ENV['DATABASE_URL'];
        $parsedUrl = parse_url($databaseUrl);

        $host = $parsedUrl['host'] ?? '';
        $port = $parsedUrl['port'] ?? '5432';
        $dbname = ltrim($parsedUrl['path'], '/');
        $username = $parsedUrl['user'] ?? '';
        $password = $parsedUrl['pass'] ?? '';

        $dsn = "pgsql:host=$host;port=$port;dbname=$dbname";

        $pdo = new \PDO($dsn, $username, $password);
        $pdo->setAttribute(\PDO::ATTR_DEFAULT_FETCH_MODE, \PDO::FETCH_ASSOC);

        return $pdo;
    }
}
