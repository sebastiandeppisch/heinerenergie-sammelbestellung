<?php

declare(strict_types=1);

namespace App\Data;

use App\Models\User;
use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
class CrmUserData extends Data
{
    public function __construct(
        public string $id,
        public string $name,
        public string $email,
    ) {}

    public static function fromUser(User $user): self
    {
        return new self(
            id: $user->uuid,
            name: $user->first_name.' '.$user->last_name,
            email: $user->email,
        );
    }
}
