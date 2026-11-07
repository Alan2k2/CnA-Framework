<?php

declare(strict_types=1);

namespace CnA\Orm\Relations;

use CnA\Orm\Model;
use CnA\QueryBuilder\QueryBuilder;

abstract class Relation
{
    public function __construct(
        protected QueryBuilder $query,
        protected Model $parent,
        protected string $relatedClass
    ) {
    }

    abstract public function addEagerConstraints(array $models): void;

    abstract public function match(array $models, array $results, string $relation): array;

    abstract public function getResults(): mixed;

    public function getQuery(): QueryBuilder
    {
        return $this->query;
    }

    public function getParent(): Model
    {
        return $this->parent;
    }

    public function getRelatedClass(): string
    {
        return $this->relatedClass;
    }
}

