<?php
declare(strict_types=1);

namespace Dalo;

final class Database
{
    private \PDO $pdo;

    public function __construct(array $config)
    {
        $dsn = sprintf('pgsql:host=%s;port=%s;dbname=%s', $config['host'], $config['port'], $config['name']);
        $this->pdo = new \PDO($dsn, $config['user'], $config['password'], [
            \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
            \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,
            \PDO::ATTR_EMULATE_PREPARES => false,
        ]);
    }

    public function pdo(): \PDO
    {
        return $this->pdo;
    }
}
