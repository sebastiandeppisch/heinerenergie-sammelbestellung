<?php

declare(strict_types=1);

namespace App\Data;

use App\Enums\FieldType;
use App\Models\MapPointField;
use App\Models\MapPointFieldOption;
use App\Services\MapPointImageAccess;
use Illuminate\Support\Carbon;
use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;
use Throwable;

/**
 * A stored value of a field of a category or characteristic, labelled as the field was when the value was entered.
 */
#[TypeScript]
class MapPointFieldValueData extends Data
{
    public function __construct(
        public string $id,
        /** The category field, null once the field was deleted. */
        public ?string $field_id,
        public FieldType $type,
        public string $label,
        /**
         * @var int|float|string|array<string>|null
         */
        public null|int|float|string|array $value,
        /** The value as shown to people: option labels instead of option values, German number and date formats. */
        public string $display_value,
        public bool $is_public,
        /**
         * The images of an image field. Its value is left out, because the paths are internal.
         *
         * @var array<int, ImageData>
         */
        public array $images = [],
        /** The characteristic of the point the field belongs to, null for fields of the category and former values. */
        public ?string $characteristic_id = null,
    ) {}

    public static function fromModel(MapPointField $model, bool $isPublic, ?string $characteristicId = null): self
    {
        $isImage = $model->type === FieldType::IMAGE;

        return new self(
            id: $model->uuid,
            field_id: $model->formField?->uuid,
            type: $model->type,
            label: $model->label,
            value: $isImage ? null : $model->value,
            display_value: self::displayValue($model),
            is_public: $isPublic,
            images: $isImage ? self::images($model) : [],
            characteristic_id: $characteristicId,
        );
    }

    /**
     * @return array<int, ImageData>
     */
    private static function images(MapPointField $model): array
    {
        return array_map(
            fn (string $path): ImageData => new ImageData(basename($path), MapPointImageAccess::url($model->mapPoint, $path)),
            array_values((array) $model->value),
        );
    }

    private static function displayValue(MapPointField $model): string
    {
        $value = $model->value;

        if ($model->type === FieldType::IMAGE) {
            $count = count((array) $value);

            return $count === 1 ? '1 Bild' : $count.' Bilder';
        }

        if (in_array($model->type, [FieldType::SELECT, FieldType::RADIO, FieldType::CHECKBOX], true)) {
            $labels = $model->options->mapWithKeys(fn (MapPointFieldOption $option): array => [$option->value => $option->label]);

            return collect((array) $value)
                ->map(fn (mixed $optionValue): string => (string) ($labels[$optionValue] ?? $optionValue))
                ->implode(', ');
        }

        // Formatted by hand instead of with Number::format(), which needs the intl extension that shared hosts may lack.
        if ($model->type === FieldType::NUMBER && is_numeric($value)) {
            return is_int($value + 0)
                ? number_format((int) $value, 0, ',', '.')
                : rtrim(rtrim(number_format((float) $value, 6, ',', '.'), '0'), ',');
        }

        if ($model->type === FieldType::DATE && is_string($value)) {
            try {
                return Carbon::parse($value)->format('d.m.Y');
            } catch (Throwable) {
                return $value;
            }
        }

        return is_array($value) ? implode(', ', $value) : (string) $value;
    }
}
