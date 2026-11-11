<?php

declare(strict_types=1);

namespace App\HexNet\Data;

use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

/**
 * A new stepping stone as the admins of the campaign see it in the mail and before they confirm an action.
 */
#[TypeScript]
class HexNetModerationPointData extends Data
{
    /**
     * @param  array<int, string>  $measures
     */
    public function __construct(
        public string $campaign,
        public string $title,
        public ?string $category,
        public array $measures,
        public string $consent,
        public bool $published,
        public string $created_at,
        public string $backend_url,
    ) {}
}
