<?php

declare(strict_types=1);

namespace Josix\Repository;

use Josix\Core\Database\Connection;
use Josix\Core\Database\QueryBuilder;
use Josix\Model\Hydrator\NoteHydrator;
use Josix\Model\Note;
use PDO;

class NoteRepository extends AbstractRepository
{
    public function __construct(QueryBuilder $queryBuilder, Connection $database)
    {
        parent::__construct($queryBuilder, $database);

        $this->setTable('notes');
        $this->setModelClass(Note::class);

        $this->ensureTable();
    }

    /**
     * Return all notes ordered by newest first.
     *
     * @return Note[]
     */
    public function findAllOrderedByNewest(): array
    {
        $stmt = $this->database->getPdo()->query(
            'SELECT * FROM notes ORDER BY created_at DESC'
        );

        $rows     = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $hydrator = new NoteHydrator();

        return $hydrator->hydrate($rows);
    }

    /**
     * Auto-create the notes table if it doesn't exist (SQLite-friendly).
     */
    private function ensureTable(): void
    {
        $this->database->getPdo()->exec('
            CREATE TABLE IF NOT EXISTS notes (
                id         INTEGER PRIMARY KEY AUTOINCREMENT,
                title      TEXT    NOT NULL,
                body       TEXT    DEFAULT \'\',
                created_at TEXT    NOT NULL
            )
        ');
    }
}
