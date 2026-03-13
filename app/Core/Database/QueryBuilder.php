<?php

namespace Josix\Core\Database;

use Josix\Core\Database\Query;
use Josix\Core\Database\ValueBag;

class QueryBuilder
{
    public function select(
        string $table, 
        ?string $modelClass, 
        ?ValueBag $valueBag = null
    ): Query {
        $query = new Query(Query::SELECT, $table, $modelClass);

        if ($modelClass !== null) {
            $relationCollection = call_user_func($modelClass . '::relations');
            foreach ($relationCollection as $targetKey => $relation) {
                $query->join($relation->getJoin($table, $targetKey));
            }
        }

        return $query; 
    }
}

