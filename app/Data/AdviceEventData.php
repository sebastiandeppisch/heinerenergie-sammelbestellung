<?php

namespace App\Data;

use Illuminate\Support\Str;
use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
class AdviceEventData extends Data
{
    public function __construct(
        public int $id,
        public string $description,
        public ?string $comment,
        public string $created_at,
        public ?string $user_name,
        public ?string $initials,
    ) {}

    public static function fromModel($event): self
    {
        $user = $event->user;
        $initials = null;

        if ($user) {
            $first = Str::substr($user->first_name, 0, 1);
            $last = Str::substr($user->last_name, 0, 1);
            $initials = $first && $last ? Str::upper($first.$last) : null;
        }

        return new self(
            id: $event->id,
            description: $event->description,
            comment: $event->comment,
            created_at: $event->created_at->format('Y-m-d H:i:s'),
            user_name: $user?->name,
            initials: $initials,
        );
    }
}
