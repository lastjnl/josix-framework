<?php

namespace Josix\Model\Relation;

enum RelationTypeEnum: string
{
    case HasMany = 'hasMany';
    case HasOne = 'hasOne';
}

