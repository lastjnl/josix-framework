<?php

namespace Josix\Repository;

use Josix\Core\Database\Connection;
use Josix\Core\Database\QueryBuilder;
use Josix\Core\Database\ValueBag;
use Josix\Model\Hydrator\HydratorInterface;
use Josix\Model\Model;
use PDO;
use ReflectionClass;

abstract class AbstractRepository
{
    protected string $table;
    protected ?string $modelClass;
    protected QueryBuilder $queryBuilder;
    protected Connection $database;

    public function __construct(QueryBuilder $queryBuilder, Connection $database)
    {
        $this->queryBuilder = $queryBuilder;
        $this->database = $database;
    }

    public function findBy(ValueBag $valueBag): array
    {
        $query = $this->queryBuilder->select(
            $this->table, 
            $this->modelClass, 
            $valueBag
        );

        $resultData = $this->database->execute($query);
        $hydrator   = $this->findHydrator();

        return $hydrator ? $hydrator->hydrate($resultData) : $resultData;
    }

    public function findAll(): array
    {
        $query = $this->queryBuilder->select($this->table, $this->modelClass);
        $resultData = $this->database->execute($query);
        $hydrator = $this->findHydrator();

        return $hydrator ? $hydrator->hydrate($resultData) : $resultData;
    }

    /**
     * Find a single record by its primary key.
     */
    public function findById(int|string $id): ?Model
    {
        $primaryKey = $this->getPrimaryKey();
        $stmt = $this->database->getPdo()->prepare(
            "SELECT * FROM {$this->table} WHERE {$primaryKey} = :id LIMIT 1"
        );
        $stmt->execute([':id' => $id]);

        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($row === false) {
            return null;
        }

        $hydrator = $this->findHydrator();
        $results  = $hydrator ? $hydrator->hydrate([$row]) : [$row];

        return $results[0] ?? null;
    }

    /**
     * Insert or update a model.
     * If the model has a primary-key value it updates; otherwise it inserts.
     *
     * @return int|string The primary-key value of the saved record.
     */
    public function save(Model $model): int|string
    {
        $primaryKey = $this->getPrimaryKey();
        $properties = $this->getModelProperties();
        $existingId = $model->get($primaryKey);

        if ($existingId !== null) {
            return $this->update($model, $primaryKey, $properties);
        }

        return $this->insert($model, $primaryKey, $properties);
    }

    /**
     * Delete a record by its primary key.
     */
    public function delete(int|string $id): bool
    {
        $primaryKey = $this->getPrimaryKey();
        $stmt = $this->database->getPdo()->prepare(
            "DELETE FROM {$this->table} WHERE {$primaryKey} = :id"
        );

        return $stmt->execute([':id' => $id]);
    }

    public function setTable(string $table): void
    {
        $this->table = $table;
    }

    public function setModelClass(string $modelClass): void
    {
        $this->modelClass = $modelClass;
    }

    // ── Private helpers ─────────────────────────────────────

    private function insert(Model $model, string $primaryKey, array $properties): int|string
    {
        // Exclude the primary key when inserting (auto-increment)
        $columns = array_filter(
            array_keys($properties),
            fn(string $col) => $col !== $primaryKey
        );

        $placeholders = array_map(fn(string $col) => ':' . $col, $columns);

        $sql = sprintf(
            'INSERT INTO %s (%s) VALUES (%s)',
            $this->table,
            implode(', ', $columns),
            implode(', ', $placeholders)
        );

        $params = [];
        foreach ($columns as $col) {
            $params[':' . $col] = $model->get($col);
        }

        $stmt = $this->database->getPdo()->prepare($sql);
        $stmt->execute($params);

        return $this->database->getPdo()->lastInsertId();
    }

    private function update(Model $model, string $primaryKey, array $properties): int|string
    {
        $columns = array_filter(
            array_keys($properties),
            fn(string $col) => $col !== $primaryKey
        );

        $setClauses = array_map(fn(string $col) => "{$col} = :{$col}", $columns);

        $sql = sprintf(
            'UPDATE %s SET %s WHERE %s = :pk_value',
            $this->table,
            implode(', ', $setClauses),
            $primaryKey
        );

        $params = [':pk_value' => $model->get($primaryKey)];
        foreach ($columns as $col) {
            $params[':' . $col] = $model->get($col);
        }

        $stmt = $this->database->getPdo()->prepare($sql);
        $stmt->execute($params);

        return $model->get($primaryKey);
    }

    /**
     * Returns the primary key column name (first key from tableProperties).
     */
    private function getPrimaryKey(): string
    {
        return array_key_first($this->getModelProperties());
    }

    /**
     * Returns the column → type map from the model class.
     */
    private function getModelProperties(): array
    {
        return call_user_func($this->modelClass . '::tableProperties');
    }

    private function findHydrator(): ?HydratorInterface
    {
        $reflectionClass = new ReflectionClass($this->modelClass);
        $hydrator = "Josix\\Model\\Hydrator\\" . $reflectionClass->getShortName() . "Hydrator";

        return class_exists($hydrator) ? new $hydrator : null;
    }
}

