<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\Pivot;

class GroupUser extends Pivot
{
    protected function casts(): array
    {
        return [
            'is_admin' => 'boolean',
        ];
    }
}
