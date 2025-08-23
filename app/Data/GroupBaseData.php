<?php

namespace App\Data;

use App\Models\Group;
use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
class GroupBaseData extends Data
{
    public function __construct(
        public string $id,
        public string $name,
        public ?string $description,
        public ?string $logo_path,
    ) {}

    public static function fromModel(Group $group): self
    {
        return new self(
            id: $group->uuid,
            name: $group->name,
            description: $group->description,
            logo_path: $group->full_logo_path ? url($group->full_logo_path) : null,
        );
    }
}
