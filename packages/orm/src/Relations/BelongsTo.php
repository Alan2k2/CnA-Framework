<?php

declare(strict_types=1);

namespace CnA\Orm\Relations;

use CnA\Orm\Model;

class BelongsTo extends Relation
{
    public function __construct(
        mixed $query,
        Model $parent,
        string $relatedClass,
        protected string $foreignKey,
        protected string $ownerKey = 'id'
    ) {
        parent::__construct($query, $parent, $relatedClass);
    }

    public function addEagerConstraints(array $models): void
    {
        $keys = array_values(array_filter(array_unique(array_map(fn($m) => $m->getAttribute($this->foreignKey), $models))));
        $this->query->whereIn($this->ownerKey, $keys);
    }

    public function match(array $models, array $results, string $relation): array
    {
        $dictionary = [];
        foreach ($results as $result) {
            $key = $result->getAttribute($this->ownerKey);
            $dictionary[$key] = $result;
        }

        foreach ($models as $model) {
            $foreign = $model->getAttribute($this->foreignKey);
            $model->setRelation($relation, $dictionary[$foreign] ?? null);
        }

        return $models;
    }

    public function getResults(): ?Model
    {
        $foreignValue = $this->parent->getAttribute($this->foreignKey);
        if ($foreignValue === null) {
            return null;
        }

        $record = $this->query->where($this->ownerKey, $foreignValue)->first();
        return $record ? new $this->relatedClass($record) : null;
    }
}

