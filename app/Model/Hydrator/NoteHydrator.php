<?php

declare(strict_types=1);

namespace Josix\Model\Hydrator;

use Josix\Model\Note;

class NoteHydrator implements HydratorInterface
{
    /**
     * @param  array<int, array<string, mixed>> $data
     * @return Note[]
     */
    public function hydrate(array $data): array
    {
        return array_map(function (array $row): Note {
            $note = new Note();
            $note->set('id', (int) $row['id']);
            $note->set('title', (string) $row['title']);
            $note->set('body', (string) ($row['body'] ?? ''));
            $note->set('created_at', (string) $row['created_at']);

            return $note;
        }, $data);
    }
}
