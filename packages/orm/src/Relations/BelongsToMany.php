<?php

declare(strict_types=1);

namespace CnA\Orm\Relations;

use CnA\Orm\Model;

class BelongsToMany extends Relation
{
    public function __construct(
        mixed $query,
        Model $parent,
        string $relatedClass,
        protected string $table,
        protected string $foreignPivotKey,
        protected string $relatedPivotKey,
        protected string $parentKey = 'id',
        protected string $relatedKey = 'id'
    ) {
        parent::__construct($query, $parent, $relatedClass);
    }

    public function addEagerConstraints(array $models): void
    {
        $keys = array_values(array_filter(array_unique(array_map(fn($m) => $m->getAttribute($this->parentKey), $models))));
        $relatedTable = $this->getRelatedTable();
        $this->query
            ->join($this->table, "{$this->table}.{$this->relatedPivotKey}", '=', "{$relatedTable}.{$this->relatedKey}")
            ->whereIn("{$this->table}.{$this->foreignPivotKey}", $keys);
    }

    public function match(array $models, array $results, string $relation): array
    {
        $dictionary = [];
        foreach ($results as $result) {
            $fk = $result->getAttribute($this->foreignPivotKey);
            $dictionary[$fk][] = $result;
        }

        foreach ($models as $model) {
            $key = $model->getAttribute($this->parentKey);
            $model->setRelation($relation, $dictionary[$key] ?? []);
        }

        return $models;
    }

    public function getResults(): array
    {
        $parentKeyVal = $this->parent->getAttribute($this->parentKey);
        $relatedTable = $this->getRelatedTable();
        $records = $this->query
            ->join($this->table, "{$this->table}.{$this->relatedPivotKey}", '=', "{$relatedTable}.{$this->relatedKey}")
            ->where("{$this->table}.{$this->foreignPivotKey}", $parentKeyVal)
            ->get();

        return array_map(fn($r) => new $this->relatedClass($r), $records);
    }

    public function getRelatedTable(): string
    {
        if (method_exists($this->query, 'getFrom') && $this->query->getFrom() !== '') {
            return $this->query->getFrom();
        }

        return (new $this->relatedClass())->getTable();
    }
}

