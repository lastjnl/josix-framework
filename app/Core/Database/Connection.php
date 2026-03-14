<?php

namespace Josix\Core\Database;

use Josix\Core\Database\Query;
use Josix\Core\Env\Env;
use PDO;
use PDOException;
use RuntimeException;

class Connection
{
    private PDO $connection;

    public function __construct()
    {
        $driver = strtolower(trim((string) (Env::getString('DB_DRIVER') ?? '')));
        $driver = $driver !== '' ? $driver : 'sqlite';

        match ($driver) {
            'sqlite' => $this->createSqliteConnection(
                Env::getString('DB_PATH') ?: ':memory:'
            ),
            default => $this->createMysqlConnection(
                (string) (Env::getString('DBHOST') ?? ''),
                (string) (Env::getString('DBNAME') ?? ''),
                (string) (Env::getString('DBUSER') ?? ''),
                (string) (Env::getString('DBPASS') ?? ''),
            ),
        };
    }

    private function createMysqlConnection(
        string $host,
        string $dbname,
        string $username,
        string $password
    ): void {
        if ($host === '' || $dbname === '' || $username === '') {
            throw new RuntimeException(
                'MySQL selected but DBHOST, DBNAME, or DBUSER is missing. '
                . 'Set DB_DRIVER=sqlite for local development, or provide full MySQL env values.'
            );
        }

        try {
            $this->connection = new PDO(
                "mysql:host=$host;dbname=$dbname;charset=utf8mb4",
                $username,
                $password
            );
            $this->connection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch (PDOException $e) {
            throw new RuntimeException(
                'Failed to connect using MySQL. Ensure pdo_mysql is installed in PHP and DB env values are correct.',
                0,
                $e
            );
        }
    }

    private function createSqliteConnection(string $path): void
    {
        $this->connection = new PDO("sqlite:$path");
        $this->connection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $this->connection->exec('PRAGMA journal_mode=WAL');
        $this->connection->exec('PRAGMA foreign_keys=ON');
    }

    public function getPdo(): PDO
    {
        return $this->connection;
    }

    public function execute(Query $query): array|object
    {
        $statement = $this->connection->prepare($query->getSql());
        $statement->execute($query->getParameters());

        return $statement->fetchAll(PDO::FETCH_ASSOC);
    }
}
