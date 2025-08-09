<?php

namespace App\Models\Traits;

use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Builder;

trait HasUuid
{
    protected function performInsert(Builder $query): bool
    {
        if (!$this->uuid) {
            $this->uuid = (string) Str::uuid();
        }
        
        return parent::performInsert($query);
    }

    public function getRouteKeyName(): string
    {
        return 'uuid';
    }
}
