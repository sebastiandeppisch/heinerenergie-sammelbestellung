<?php

namespace App\Data;

use App\Models\Group;
use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
class GroupData extends Data
{
    public int $users_count = 0;

    public int $advices_count = 0;

    public function __construct(
        public string $id,
        public string $name,
        public ?string $description,
        public ?string $logo_path,
        public ?string $parent_id,
        public bool $accepts_transfers,
        public bool $userCanActAsAdmin,
        ?int $users_count,
        ?int $advices_count,
    ) {
        $this->users_count = $users_count ?? 0;
        $this->advices_count = $advices_count ?? 0;
    }

    public static function fromModel(Group $group): self
    {
        $currentUser = auth()->user();

        $canActAsAdmin = $group->admins()->where('user_id', $currentUser->id)->exists();

        return new self(
            id: $group->id,
            name: $group->name,
            description: $group->description,
            logo_path: $group->full_logo_path ? url($group->full_logo_path) : null,
            parent_id: $group->parent_id,
            accepts_transfers: $group->accepts_transfers,
            userCanActAsAdmin: $canActAsAdmin,
            users_count: $group->users()->count(),
            advices_count: $group->advices()->count(),
        );
    }
}
