<?php

namespace App\Data;

use App\Models\Group;
use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
class GroupTreeItem extends Data
{
    public function __construct(
        public string $id,
        public string $name,
        public bool $selected,
        public bool $expanded,
        public ?string $parent_id,
        public ?string $logo_path
    ) {
    }
}
