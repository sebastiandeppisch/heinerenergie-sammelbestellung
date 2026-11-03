<?php

declare(strict_types=1);

namespace App\Data;

use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

/**
 * A form that adds to existing points, offered as the follow-up form of a map point target.
 */
#[TypeScript]
class MapPointFollowUpFormData extends Data
{
    /**
     * @param  array<int, string>  $category_ids  the categories whose points it fits
     */
    public function __construct(
        public string $id,
        public string $name,
        public ?string $category_id,
        public array $category_ids,
    ) {}
}
