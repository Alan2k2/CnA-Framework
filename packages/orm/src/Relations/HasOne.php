<?php

declare(strict_types=1);

namespace CnA\Orm\Relations;

use CnA\Orm\Model;

class HasOne extends Relation
{
    public function __construct(
        mixed $query,
        Model $parent,
        string $relatedClass,
        protected string $foreignKey,
        protected string $localKey = 'id'
    ) {
        parent::__construct($query, $parent, $relatedClass);
    }

    public function addEagerConstraints(array $models): void
    {
        $keys = array_values(array_filter(array_unique(array_map(fn($m) => $m->getAttribute($this->localKey), $models))));
        $this->query->whereIn($this->foreignKey, $keys);
    }

    public function match(array $models, array $results, string $relation): array
    {
        $dictionary = [];
        foreach ($results as $result) {
            $fk = $result->getAttribute($this->foreignKey);
            $dictionary[$fk] = $result;
        }

        foreach ($models as $model) {
            $key = $model->getAttribute($this->localKey);
            $model->setRelation($relation, $dictionary[$key] ?? null);
        }

        return $models;
    }

    public function getResults(): ?Model
    {
        $parentKey = $this->parent->getAttribute($this->localKey);
        $record = $this->query->where($this->foreignKey, $parentKey)->first();
        return $record ? new $this->relatedClass($record) : null;
    }
}

