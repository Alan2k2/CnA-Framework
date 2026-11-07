<?php

declare(strict_types=1);

namespace CnA\Orm;

use CnA\Contracts\Database\ConnectionInterface;
use CnA\Contracts\Database\QueryBuilderInterface;
use CnA\Contracts\Exceptions\LazyLoadingViolationException;
use CnA\Contracts\Exceptions\NotFoundException;
use CnA\Database\ConnectionManager;
use CnA\Orm\Relations\BelongsTo;
use CnA\Orm\Relations\BelongsToMany;
use CnA\Orm\Relations\HasMany;
use CnA\Orm\Relations\HasOne;
use CnA\Orm\Relations\Relation;
use CnA\QueryBuilder\QueryBuilder;
use JsonSerializable;

abstract class Model implements JsonSerializable
{
    protected ?string $table = null;
    protected string $primaryKey = 'id';
    protected ?string $connectionName = null;
    protected bool $timestamps = true;

    protected array $attributes = [];
    protected array $original = [];
    protected array $relations = [];

    protected array $fillable = [];
    protected array $guarded = ['*'];
    protected array $casts = [];
    protected array $hidden = [];

    public static bool $preventLazyLoading = false;

    public function __construct(array $attributes = [])
    {
        $this->fill($attributes);
        $this->syncOriginal();
    }

    public static function newFromBuilder(array $attributes = []): static
    {
        $instance = new static();
        $instance->setRawAttributes($attributes, true);
        return $instance;
    }

    public function setRawAttributes(array $attributes, bool $sync = false): static
    {
        $this->attributes = $attributes;
        if ($sync) {
            $this->syncOriginal();
        }
        return $this;
    }

    public static function preventLazyLoading(bool $value = true): void
    {
        static::$preventLazyLoading = $value;
    }

    public static function query(): QueryBuilder
    {
        $instance = new static();
        return $instance->newQuery();
    }

    public function newQuery(): QueryBuilder
    {
        $conn = $this->getConnection();
        return (new QueryBuilder($conn))->from($this->getTable());
    }

    public function getTable(): string
    {
        if ($this->table !== null) {
            return $this->table;
        }

        $shortName = (new \ReflectionClass($this))->getShortName();
        // Lowercase and pluralize simple naming
        $snake = strtolower(preg_replace('/(?<!^)[A-Z]/', '_$0', $shortName));
        return str_ends_with($snake, 's') ? $snake : $snake . 's';
    }

    public function getKeyName(): string
    {
        return $this->primaryKey;
    }

    public function getKey(): mixed
    {
        return $this->getAttribute($this->primaryKey);
    }

    public function getConnection(): ConnectionInterface
    {
        /** @var ConnectionManager $manager */
        $manager = app(ConnectionManager::class);
        return $manager->connection($this->connectionName);
    }

    public function fill(array $attributes): static
    {
        foreach ($attributes as $key => $value) {
            if ($this->isFillable($key)) {
                $this->setAttribute($key, $value);
            }
        }
        return $this;
    }

    public function isFillable(string $key): bool
    {
        if (in_array($key, $this->fillable, true)) {
            return true;
        }
        if ($this->guarded === ['*'] && empty($this->fillable)) {
            return false;
        }
        return !in_array($key, $this->guarded, true);
    }

    public function getAttribute(string $key): mixed
    {
        if (array_key_exists($key, $this->attributes)) {
            return $this->castAttribute($key, $this->attributes[$key]);
        }

        if (array_key_exists($key, $this->relations)) {
            return $this->relations[$key];
        }

        if (method_exists($this, $key)) {
            return $this->getRelationValue($key);
        }

        return null;
    }

    public function setAttribute(string $key, mixed $value): static
    {
        $this->attributes[$key] = $value;
        return $this;
    }

    public function setRelation(string $relation, mixed $value): static
    {
        $this->relations[$relation] = $value;
        return $this;
    }

    public function relationLoaded(string $key): bool
    {
        return array_key_exists($key, $this->relations);
    }

    public function getRelations(): array
    {
        return $this->relations;
    }

    protected function getRelationValue(string $key): mixed
    {
        if (static::$preventLazyLoading) {
            throw new LazyLoadingViolationException(static::class, $key);
        }

        /** @var Relation $relation */
        $relation = $this->{$key}();
        $results = $relation->getResults();
        $this->setRelation($key, $results);

        return $results;
    }

    public static function create(array $attributes = []): static
    {
        $model = new static($attributes);
        $model->save();
        return $model;
    }

    public static function find(mixed $id): ?static
    {
        $instance = new static();
        $record = $instance->newQuery()->where($instance->getKeyName(), $id)->first();
        if (!$record) {
            return null;
        }
        $model = new static();
        $model->attributes = $record;
        $model->syncOriginal();
        return $model;
    }

    public static function findOrFail(mixed $id): static
    {
        $record = static::find($id);
        if ($record === null) {
            throw new NotFoundException("Model [" . static::class . "] not found for ID: {$id}");
        }
        return $record;
    }

    public static function all(): array
    {
        $instance = new static();
        $records = $instance->newQuery()->get();
        return array_map(function ($r) {
            $m = new static();
            $m->attributes = $r;
            $m->syncOriginal();
            return $m;
        }, $records);
    }

    public static function with(array|string $relations): ModelQuery
    {
        $instance = new static();
        return (new ModelQuery($instance))->with($relations);
    }

    public function save(): bool
    {
        $now = date('Y-m-d H:i:s');
        if ($this->timestamps) {
            if (!$this->exists()) {
                $this->setAttribute('created_at', $this->getAttribute('created_at') ?? $now);
            }
            $this->setAttribute('updated_at', $now);
        }

        if ($this->exists()) {
            $dirty = $this->getDirty();
            if (empty($dirty)) {
                return true;
            }
            $this->newQuery()->where($this->getKeyName(), $this->getKey())->update($dirty);
        } else {
            $id = $this->newQuery()->insert($this->attributes);
            if ($id && is_numeric($id) && (int) $id > 0) {
                $this->setAttribute($this->getKeyName(), (int) $id);
            }
        }

        $this->syncOriginal();
        return true;
    }

    public function delete(): bool
    {
        if (!$this->exists()) {
            return false;
        }

        $this->newQuery()->where($this->getKeyName(), $this->getKey())->delete();
        return true;
    }

    public function exists(): bool
    {
        return isset($this->original[$this->getKeyName()]);
    }

    public function getDirty(): array
    {
        $dirty = [];
        foreach ($this->attributes as $key => $value) {
            if (!array_key_exists($key, $this->original) || $this->original[$key] !== $value) {
                $dirty[$key] = $value;
            }
        }
        return $dirty;
    }

    public function syncOriginal(): void
    {
        $this->original = $this->attributes;
    }

    // Relationships
    public function hasMany(string $related, ?string $foreignKey = null, ?string $localKey = null): HasMany
    {
        $instance = new $related();
        $foreignKey = $foreignKey ?? $this->getForeignKey();
        $localKey = $localKey ?? $this->getKeyName();

        return new HasMany($instance->newQuery(), $this, $related, $foreignKey, $localKey);
    }

    public function belongsTo(string $related, ?string $foreignKey = null, ?string $ownerKey = null): BelongsTo
    {
        $instance = new $related();
        $foreignKey = $foreignKey ?? $instance->getForeignKey();
        $ownerKey = $ownerKey ?? $instance->getKeyName();

        return new BelongsTo($instance->newQuery(), $this, $related, $foreignKey, $ownerKey);
    }

    public function hasOne(string $related, ?string $foreignKey = null, ?string $localKey = null): HasOne
    {
        $instance = new $related();
        $foreignKey = $foreignKey ?? $this->getForeignKey();
        $localKey = $localKey ?? $this->getKeyName();

        return new HasOne($instance->newQuery(), $this, $related, $foreignKey, $localKey);
    }

    public function belongsToMany(
        string $related,
        ?string $table = null,
        ?string $foreignPivotKey = null,
        ?string $relatedPivotKey = null
    ): BelongsToMany {
        $instance = new $related();
        $table = $table ?? $this->joiningTable($related);
        $foreignPivotKey = $foreignPivotKey ?? $this->getForeignKey();
        $relatedPivotKey = $relatedPivotKey ?? $instance->getForeignKey();

        return new BelongsToMany($instance->newQuery(), $this, $related, $table, $foreignPivotKey, $relatedPivotKey);
    }

    public function getForeignKey(): string
    {
        $short = (new \ReflectionClass($this))->getShortName();
        return strtolower($short) . '_id';
    }

    public function morphMany(string $related, string $name, ?string $type = null, ?string $id = null, ?string $localKey = null): \CnA\Orm\Relations\MorphMany
    {
        $instance = new $related();
        $type = $type ?? $name . '_type';
        $id = $id ?? $name . '_id';
        $localKey = $localKey ?? $this->getKeyName();

        return new \CnA\Orm\Relations\MorphMany($instance->newQuery(), $this, $related, $type, $id, $localKey);
    }

    public function morphTo(?string $name = null, ?string $type = null, ?string $id = null): \CnA\Orm\Relations\MorphTo
    {
        $caller = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2)[1]['function'] ?? 'morph';
        $name = $name ?? $caller;
        $type = $type ?? $name . '_type';
        $id = $id ?? $name . '_id';

        return new \CnA\Orm\Relations\MorphTo($this, $type, $id);
    }

    public function getMorphClass(): string
    {
        return static::class;
    }

    protected function joiningTable(string $related): string
    {
        $base = (new \ReflectionClass($this))->getShortName();
        $rel = (new \ReflectionClass($related))->getShortName();
        $models = [strtolower($base), strtolower($rel)];
        sort($models);
        return implode('_', $models);
    }

    protected function castAttribute(string $key, mixed $value): mixed
    {
        if ($value === null) {
            return null;
        }

        $cast = $this->casts[$key] ?? null;
        if ($cast === null) {
            return $value;
        }

        return match ($cast) {
            'int', 'integer' => (int) $value,
            'float', 'double' => (float) $value,
            'bool', 'boolean' => (bool) $value,
            'string' => (string) $value,
            'array', 'json' => is_string($value) ? json_decode($value, true) : (array) $value,
            default => $value,
        };
    }

    public function toArray(): array
    {
        $attributes = $this->attributes;
        foreach ($this->hidden as $h) {
            unset($attributes[$h]);
        }

        foreach ($this->relations as $relationName => $relationValue) {
            if ($relationValue instanceof Model) {
                $attributes[$relationName] = $relationValue->toArray();
            } elseif (is_array($relationValue)) {
                $attributes[$relationName] = array_map(
                    fn($item) => $item instanceof Model ? $item->toArray() : $item,
                    $relationValue
                );
            }
        }

        return $attributes;
    }

    public function jsonSerialize(): mixed
    {
        return $this->toArray();
    }

    public function __get(string $name): mixed
    {
        return $this->getAttribute($name);
    }

    public function __set(string $name, mixed $value): void
    {
        $this->setAttribute($name, $value);
    }

    public function __isset(string $name): bool
    {
        return $this->getAttribute($name) !== null;
    }

    public static function __callStatic(string $method, array $parameters): mixed
    {
        return (new static())->newQuery()->{$method}(...$parameters);
    }
}

