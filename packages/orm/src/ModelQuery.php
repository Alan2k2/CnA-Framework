<?php

declare(strict_types=1);

namespace CnA\Orm;

use CnA\Contracts\Database\QueryBuilderInterface;
use CnA\Orm\Relations\Relation;

class ModelQuery
{
    protected array $eagerLoad = [];
    protected QueryBuilderInterface $query;

    public function __construct(protected Model $model)
    {
        $this->query = $model->newQuery();
    }

    public function with(array|string $relations): static
    {
        $this->eagerLoad = array_merge($this->eagerLoad, (array) $relations);
        return $this;
    }

    public function where(string|callable $column, mixed $operator = null, mixed $value = null): static
    {
        $this->query->where($column, $operator, $value);
        return $this;
    }

    public function orderBy(string $column, string $direction = 'asc'): static
    {
        $this->query->orderBy($column, $direction);
        return $this;
    }

    public function limit(int $value): static
    {
        $this->query->limit($value);
        return $this;
    }

    public function get(): array
    {
        $records = $this->query->get();
        $class = get_class($this->model);

        /** @var array<Model> $models */
        $models = array_map(function ($r) use ($class) {
            $m = new $class();
            $m->setAttribute($m->getKeyName(), $r[$m->getKeyName()] ?? null);
            foreach ($r as $k => $v) {
                $m->setAttribute($k, $v);
            }
            $m->syncOriginal();
            return $m;
        }, $records);

        if (empty($models) || empty($this->eagerLoad)) {
            return $models;
        }

        foreach ($this->eagerLoad as $relationName) {
            $this->eagerLoadRelation($models, $relationName);
        }

        return $models;
    }

    public function first(): ?Model
    {
        $this->query->limit(1);
        $results = $this->get();
        return $results[0] ?? null;
    }

    public function count(): int
    {
        return $this->query->count();
    }

    protected function eagerLoadRelation(array &$models, string $relationName): void
    {
        if (empty($models)) {
            return;
        }

        $firstModel = $models[0];
        if (!method_exists($firstModel, $relationName)) {
            throw new \InvalidArgumentException("Relation [{$relationName}] not found on model [" . get_class($firstModel) . "].");
        }

        /** @var Relation $relation */
        $relation = $firstModel->{$relationName}();
        $relation->addEagerConstraints($models);

        $relatedRecords = $relation->getQuery()->get();
        $relatedClass = $relation->getRelatedClass();

        $relatedModels = array_map(function ($r) use ($relatedClass) {
            $rm = new $relatedClass();
            foreach ($r as $k => $v) {
                $rm->setAttribute($k, $v);
            }
            $rm->syncOriginal();
            return $rm;
        }, $relatedRecords);

        $models = $relation->match($models, $relatedModels, $relationName);
    }
}

