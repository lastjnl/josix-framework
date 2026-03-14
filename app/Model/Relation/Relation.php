<?php

namespace Josix\Model\Relation;

use Josix\Core\Database\Query;

class Relation
{
    public function __construct(
        private RelationTypeEnum $type,
        protected ?string $modelKey,
        protected string $relationModelKey,
        protected string $relationModel
    ) {}

    public function getRelationModel(): string
    {
        return $this->relationModel;
    }

    public function getType(): RelationTypeEnum
    {
        return $this->type;
    }

    public function getTableName(): string
    {
        return Query::sanitize($this->relationModel);
    }

    public function getJoin(string $modelTable, string $targetKey): Join
    {
        $relationTable = Query::sanitize($this->relationModel);
        $modelTable = Query::sanitize($modelTable);
        $modelKey = $this->modelKey ?? $this->relationModelKey;

        $jsonObjectString = self::buildJsonObject($this->relationModel, $relationTable);

        // Use a correlated subquery to avoid cartesian product when multiple relations are joined
        $subquery = "(SELECT JSON_ARRAYAGG(JSON_OBJECT(" . $jsonObjectString . ")) FROM " . $relationTable
            . " WHERE " . $relationTable . ".`" . $this->relationModelKey . "` = " . $modelTable . ".`" . $modelKey . "`)"
            . " AS `" . $targetKey . "`";

        return new Join("", $subquery);
    }

    /**
     * Recursively builds the JSON_OBJECT column list for a model,
     * including nested relations as sub-selects.
     */
    public static function buildJsonObject(string $modelClass, string $table): string
    {
        $tableProperties = array_keys(call_user_func($modelClass . '::tableProperties'));

        $jsonParts = [];
        foreach ($tableProperties as $property) {
            $jsonParts[] = "'" . $property . "', " . $table . ".`" . $property . "`";
        }

        // Include nested relations
        $relations = call_user_func($modelClass . '::relations');
        foreach ($relations as $nestedKey => $nestedRelation) {
            $nestedTable = Query::sanitize($nestedRelation->getRelationModel());
            $nestedJson = self::buildJsonObject($nestedRelation->getRelationModel(), $nestedTable);
            $nestedModelKey = $nestedRelation->modelKey ?? $nestedRelation->relationModelKey;

            if ($nestedRelation->getType() === RelationTypeEnum::HasOne) {
                $jsonParts[] = "'" . $nestedKey . "', (SELECT JSON_OBJECT(" . $nestedJson . ") FROM " . $nestedTable
                    . " WHERE " . $nestedTable . ".`" . $nestedRelation->relationModelKey . "` = " . $table . ".`" . $nestedModelKey . "` LIMIT 1)";
            } else {
                $jsonParts[] = "'" . $nestedKey . "', (SELECT JSON_ARRAYAGG(JSON_OBJECT(" . $nestedJson . ")) FROM " . $nestedTable
                    . " WHERE " . $nestedTable . ".`" . $nestedRelation->relationModelKey . "` = " . $table . ".`" . $nestedModelKey . "`)";
            }
        }

        return implode(", ", $jsonParts);
    }
}
