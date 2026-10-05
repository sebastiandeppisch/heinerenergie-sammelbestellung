<?php

declare(strict_types=1);

namespace App\Data;

use Spatie\LaravelData\Data;

/**
 * The map point a submission creates, read from the form when it was submitted.
 */
class MapPointTargetPayload extends Data
{
    /**
     * @param  array<string, mixed>  $field_values  keyed by the uuid of the category field, images as their paths
     */
    public function __construct(
        public mixed $title,
        public mixed $description,
        public mixed $coordinate,
        public ?int $category_id,
        public int $group_id,
        public array $field_values,
    ) {}
}
