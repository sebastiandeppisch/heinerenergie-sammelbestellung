<?php

namespace App\Models\Traits;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Str;

trait HasUuid
{
    protected function performInsert(Builder $query): bool
    {
        if (! $this->uuid) {
            $this->uuid = (string) Str::uuid();
        }

        return parent::performInsert($query);
    }

    public function getRouteKeyName(): string
    {
        return 'uuid';
    }

    /**
     * Retrieve the model for a bound value, doing this manually and checking for a valid UUID prevents type issues with postgres UUID fields
     */
    public function resolveRouteBinding($value, $field = null)
    {
        if (! Str::isUuid($value)) {
            throw (new ModelNotFoundException)->setModel(static::class, $value);
        }

        return $this->where($field ?? $this->getRouteKeyName(), $value)->first();
    }
}
