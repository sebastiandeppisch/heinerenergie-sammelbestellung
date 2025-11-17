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
        public ?string $new_advice_mail,
        ?int $users_count,
        ?int $advices_count,
    ) {
        $this->users_count = $users_count ?? 0;
        $this->advices_count = $advices_count ?? 0;
    }

    public static function fromModel(Group $group): self
    {

        $group->loadMissing('parent');
        $currentUser = auth()->user();

        $canActAsAdmin = $group->admins()->where('user_id', $currentUser->id)->exists();

        return new self(
            id: $group->uuid,
            name: $group->name,
            description: $group->description,
            logo_path: $group->full_logo_path ? url($group->full_logo_path) : null,
            parent_id: $group->parent ? $group->parent->uuid : null,
            accepts_transfers: $group->accepts_transfers,
            userCanActAsAdmin: $canActAsAdmin,
            new_advice_mail: $group->new_advice_mail,
            users_count: $group->users()->count(),
            advices_count: $group->advices()->count(),
        );
    }
}
