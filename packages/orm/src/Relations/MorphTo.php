<?php

declare(strict_types=1);

namespace CnA\Orm\Relations;

use CnA\Orm\Model;

class MorphTo extends Relation
{
    public function __construct(
        Model $parent,
        protected string $morphType,
        protected string $foreignKey
    ) {
        $type = (string) $parent->getAttribute($morphType);
        $relatedClass = class_exists($type) ? $type : Model::class;
        $query = class_exists($type) ? (new $type())->newQuery() : $parent->newQuery();

        parent::__construct($query, $parent, $relatedClass);
    }

    public function addEagerConstraints(array $models): void
    {
    }

    public function match(array $models, array $results, string $relation): array
    {
        return $models;
    }

    public function getResults(): ?Model
    {
        $type = (string) $this->parent->getAttribute($this->morphType);
        $id = $this->parent->getAttribute($this->foreignKey);

        if (!$type || !$id || !class_exists($type)) {
            return null;
        }

        return $type::find($id);
    }
}

