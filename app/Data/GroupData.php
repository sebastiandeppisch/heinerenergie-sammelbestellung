<?php

namespace App\Data;

use Spatie\LaravelData\Data;
use App\Models\Group;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
class GroupData extends Data
{
    public int $users_count = 0;
    public int $advices_count = 0;

    public function __construct(
        public int $id,
        public string $name,
        public ?string $description,
        public ?string $logo_path,
        public ?int $parent_id,
        public bool $accepts_transfers,
        ?int $users_count,
        ?int $advices_count,
    ) {
        $this->users_count = $users_count ?? 0;
        $this->advices_count = $advices_count ?? 0;
    }

    public static function fromModel(Group $group): self
    {
        return new self(
            id: $group->id,
            name: $group->name,
            description: $group->description,
            logo_path: $group->full_logo_path,
            parent_id: $group->parent_id,
            accepts_transfers: $group->accepts_transfers,
            users_count: $group->users_count,
            advices_count: $group->advices_count,
        );
    }
}