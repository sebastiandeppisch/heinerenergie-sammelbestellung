<?php

declare(strict_types=1);

namespace App\Data;

use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

/**
 * How a spreadsheet row ends up as a map point.
 */
#[TypeScript]
class MapPointImportRowData extends Data
{
    /**
     * @param  string  $group_name  The initiative the point belongs to. Updated points may belong to a sub initiative.
     */
    public function __construct(
        public int $row,
        public bool $is_update,
        public string $title,
        public float $lat,
        public float $lng,
        public ?string $location,
        public ?string $category,
        public bool $published,
        public string $group_name,
    ) {}
}
