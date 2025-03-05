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
        public int $id,
        public string $name,
        public AdviceStatusResult $result,
        public ?int $group_id,
        public ?string $created_at,
        public ?string $updated_at,
        public bool $visible_in_group,
    ) {}

    public static function fromModel(AdviceStatus $status, Group $context): self
    {
        return new self(
            id: $status->id,
            name: $status->name,
            result: $status->result,
            group_id: $status->group_id,
            created_at: $status->created_at?->toDateTimeString(),
            updated_at: $status->updated_at?->toDateTimeString(),
            visible_in_group: $status->isVisibleInGroup($context),
        );
    }
}
