<?php

declare(strict_types=1);

namespace CnA\Orm\Relations;

use CnA\Orm\Model;

class MorphMany extends Relation
{
    public function __construct(
        mixed $query,
        Model $parent,
        string $relatedClass,
        protected string $morphType,
        protected string $foreignKey,
        protected string $localKey = 'id'
    ) {
        parent::__construct($query, $parent, $relatedClass);
    }

    public function addEagerConstraints(array $models): void
    {
        $keys = array_values(array_filter(array_unique(array_map(fn($m) => $m->getAttribute($this->localKey), $models))));
        $this->query
            ->where($this->morphType, $this->parent->getMorphClass())
            ->whereIn($this->foreignKey, $keys);
    }

    public function match(array $models, array $results, string $relation): array
    {
        $dictionary = [];
        foreach ($results as $result) {
            $fk = $result->getAttribute($this->foreignKey);
            $dictionary[$fk][] = $result;
        }

        foreach ($models as $model) {
            $key = $model->getAttribute($this->localKey);
            $model->setRelation($relation, $dictionary[$key] ?? []);
        }

        return $models;
    }

    public function getResults(): array
    {
        $parentKey = $this->parent->getAttribute($this->localKey);
        $records = $this->query
            ->where($this->morphType, $this->parent->getMorphClass())
            ->where($this->foreignKey, $parentKey)
            ->get();

        return array_map(fn($r) => new $this->relatedClass($r), $records);
    }
}

