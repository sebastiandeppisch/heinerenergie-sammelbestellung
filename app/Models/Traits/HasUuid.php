<?php

namespace App\Models\Traits;

use Illuminate\Database\Eloquent\Builder;
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
}
