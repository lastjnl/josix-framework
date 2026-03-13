<?php

namespace Josix\Core\Database;

use Josix\Core\Database\Query;
use Josix\Core\Env\Env;
use PDO;

class Connection
{
    private PDO $connection;

    public function __construct()
    {
        $driver = Env::getString('DB_DRIVER') ?: 'mysql';

        match ($driver) {
            'sqlite' => $this->createSqliteConnection(
                Env::getString('DB_PATH') ?: ':memory:'
            ),
            default => $this->createMysqlConnection(
                Env::getString('DBHOST'),
                Env::getString('DBNAME'),
                Env::getString('DBUSER'),
                Env::getString('DBPASS'),
            ),
        };
    }

    private function createMysqlConnection(
        string $host,
        string $dbname,
        string $username,
        string $password
    ): void {
        $this->connection = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
        $this->connection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
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

