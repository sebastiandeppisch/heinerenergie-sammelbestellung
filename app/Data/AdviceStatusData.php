<?php

namespace App\Data;

use App\Enums\AdviceStatusResult;
use App\Models\AdviceStatus;
use App\Models\Group;
use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
class AdviceStatusData extends Data
{
    public function __construct(
        public string $id,
        public string $name,
        public AdviceStatusResult $result,
        public ?string $group_id,
        public bool $visible_in_group,
    ) {}

    public static function fromModel(AdviceStatus $status, Group $context): self
    {
        return new self(
            id: $status->id,
            name: $status->name,
            result: $status->result,
            group_id: $status->group_id,
            visible_in_group: $status->isVisibleInGroup($context),
        );
    }
}
