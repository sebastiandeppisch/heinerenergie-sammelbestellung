<?php

declare(strict_types=1);

namespace App\Data\Pages;

use App\Data\GroupData;
use App\Data\GroupTreeItem;
use App\ValueObjects\Polygon;
use Illuminate\Support\Collection;
use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
class GroupsIndexData extends Data
{
    public function __construct(
        /** @var Collection<int, GroupTreeItem> */
        public Collection $groupTreeItems,
        /** @var Collection<int, GroupData> */
        public Collection $groups,
        public bool $canCreateRootGroup,
        public ?GroupData $selectedGroup,
        public ?Polygon $polygon,
        public bool $canEditGroup,
        public bool $canCreateGroups,
    ) {}
}
