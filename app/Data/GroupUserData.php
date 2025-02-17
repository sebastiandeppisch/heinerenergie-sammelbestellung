<?php

namespace App\Data;

use Spatie\LaravelData\Data;

class GroupUserData extends Data
{
    public function __construct(
        public int $id,
        public string $name,
        public string $email,
        public bool $is_admin,
    ) {
    }
} 