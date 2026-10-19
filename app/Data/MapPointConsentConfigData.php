<?php

declare(strict_types=1);

namespace App\Data;

use Illuminate\Support\Collection;
use Spatie\LaravelData\Attributes\DataCollectionOf;
use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

/**
 * What the public form needs to show the consent text for the chosen sub category and characteristics.
 * The server builds the text again on submit, this only drives the preview.
 */
#[TypeScript]
class MapPointConsentConfigData extends Data
{
    /**
     * @param  array<int, string>  $base_labels
     * @param  array<int, string>  $default_category_labels  labels of the public fields of the category of the target
     * @param  array<string, array<int, string>>  $category_labels  keyed by the option value of the sub category field
     * @param  Collection<int, MapPointConsentCharacteristicData>  $characteristics
     */
    public function __construct(
        public string $text,
        public array $base_labels,
        public ?string $subcategory_field_id,
        public array $default_category_labels,
        public array $category_labels,
        public ?string $characteristics_field_id,
        #[DataCollectionOf(MapPointConsentCharacteristicData::class)]
        public Collection $characteristics,
    ) {}
}
