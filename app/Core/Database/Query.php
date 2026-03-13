<?php

namespace Josix\Core\Database;

use Josix\Model\Relation\Join;
use ReflectionClass;

class Query
{
    public const SELECT = 'select';

    private array $selectValues = [];
    private array $joins = [];
    private array $parameters = [];

    public function __construct(private string $mode, private string $table, private ?string $modelClass, )
    {
        $this->table = self::sanitize($table);
        if ($mode === self::SELECT) {
            $this->selectValues[] = $this->table . ".*";
        }
    }

    public function getParameters(): array
    {
        return $this->parameters;
    }

    public function getModelClass(): ?string
    {
        return $this->modelClass;
    }

    public function join(Join $join): void
    {
        if (strlen($join->join) > 0) {
            $this->joins[] = $join->join;
        }
        if (strlen($join->selectValue)) {
            $this->selectValues[] = $join->selectValue; 
        }
    }

    public function getSql(): string
    {
        $sql = '';
        if ($this->mode === self::SELECT) {
            $sql = "SELECT " . implode(", ", $this->selectValues) . " FROM " . $this->table;
        }

        if (count($this->joins) > 0) {
            $sql .= " " . implode(" ", $this->joins);
        }

        if ($this->modelClass !== null && count($this->joins) > 0) {
            $primaryKey = array_key_first(call_user_func($this->modelClass . '::tableProperties'));
            $sql .= " GROUP BY " . $this->table . ".`" . $primaryKey . "`";
        }

        return $sql;
    }

    public static function sanitize(string $identifier): string
    {
        if (class_exists($identifier)) {
            $identifier = (new ReflectionClass($identifier))->getShortName();
        }
        // Convert PascalCase to snake_case (RecepyIngredient → recepy_ingredient)
        $snake = ltrim(strtolower(preg_replace('/([a-z])([A-Z])/', '$1_$2', $identifier)), '_');
        // Strip everything except alphanumeric, underscore, dot
        $clean = preg_replace('/[^a-zA-Z0-9_.]/', '', $snake);

        if ($clean === '' || $clean !== $snake) {
            throw new \InvalidArgumentException(
                "Invalid SQL identifier [{$identifier}]. " .
                "Only letters, numbers, underscores and dots are allowed."
            );
        }

        // Backtick-quote each part: schema.table → `schema`.`table`
        return implode('.', array_map(
            fn($part) => '`' . $part . '`',
            explode('.', $clean)
        ));
    }
}

