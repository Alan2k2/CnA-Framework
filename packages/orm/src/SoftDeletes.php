<?php

declare(strict_types=1);

namespace CnA\Orm;

trait SoftDeletes
{
    protected bool $forceDeleting = false;

    public function isSoftDelete(): bool
    {
        return true;
    }

    public function getDeletedAtColumn(): string
    {
        return 'deleted_at';
    }

    public function delete(): bool
    {
        if ($this->forceDeleting) {
            return parent::delete();
        }

        $col = $this->getDeletedAtColumn();
        $this->setAttribute($col, date('Y-m-d H:i:s'));
        $this->newQuery()->where($this->getKeyName(), $this->getKey())->update([
            $col => $this->getAttribute($col),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        return true;
    }

    public function restore(): bool
    {
        $col = $this->getDeletedAtColumn();
        $this->setAttribute($col, null);
        $this->newQuery()->where($this->getKeyName(), $this->getKey())->update([
            $col => null,
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        return true;
    }

    public function forceDelete(): bool
    {
        $this->forceDeleting = true;
        $result = $this->delete();
        $this->forceDeleting = false;
        return $result;
    }

    public function trashed(): bool
    {
        return $this->getAttribute($this->getDeletedAtColumn()) !== null;
    }
}

