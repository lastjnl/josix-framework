<?php

declare(strict_types=1);

namespace Josix\Model;

use Josix\Model\Relation\RelationCollection;

class Note extends Model implements ModelInterface
{
    public static function tableProperties(): array
    {
        return [
            'id'         => 'int',
            'title'      => 'string',
            'body'       => 'string',
            'created_at' => 'string',
        ];
    }

    public static function relations(): RelationCollection
    {
        return new RelationCollection([]);
    }
}
