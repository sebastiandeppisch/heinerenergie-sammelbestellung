<?php

namespace App\Data\Pages;

use App\Data\GroupData;
use App\ValueObjects\Polygon;
use Illuminate\Support\Collection;
use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
class GroupsIndexData extends Data
{
    public function __construct(
        /** @var Collection|array<GroupTreeItem> */
        public Collection $groupTreeItems,
        /** @var Collection|array<GroupData> */
        public Collection $groups,
        public bool $canCreateRootGroup,
        public ?GroupData $selectedGroup,
        public ?Polygon $polygon,
        public bool $canEditGroup,
        public bool $canCreateGroups,
    ) {}
}
