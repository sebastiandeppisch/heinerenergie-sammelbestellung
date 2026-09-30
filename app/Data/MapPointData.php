<?php

declare(strict_types=1);

namespace App\Data;

use App\Models\Advice;
use App\Models\FormSubmission;
use App\Models\MapPoint;
use App\Models\MapPointCategory;
use App\Models\MapPointField;
use App\Services\MapPointFieldService;
use App\ValueObjects\Coordinate;
use App\ValueObjects\MapPointCategoryTree;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Spatie\LaravelData\Attributes\DataCollectionOf;
use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
class MapPointData extends Data
{
    /**
     * @param  Collection<int, MapPointFieldValueData>  $fields  values of the fields of the point's category
     * @param  Collection<int, MapPointFieldValueData>  $former_fields  values of fields the point no longer has, never sent to the public
     */
    public function __construct(
        public string $id,
        public Coordinate $coordinate,
        public string $title,
        public string $description,
        public bool $published,
        public string $userReadablePointableType,
        public Carbon $created_at,
        public string $group_id,
        public ?string $category_id = null,
        public ?string $location = null,
        #[DataCollectionOf(MapPointFieldValueData::class)]
        public Collection $fields = new Collection,
        #[DataCollectionOf(MapPointFieldValueData::class)]
        public Collection $former_fields = new Collection,
    ) {}

    /**
     * Field values may hold personal data such as contact details, so by default only public values are included.
     *
     * @param  bool  $onlyPublic  Pass false only for admins of the point: then internal and former values are included.
     * @param  MapPointCategoryTree|null  $tree  Pass a loaded tree when converting many points, otherwise it is loaded for each one.
     * @param  array<int, int>|null  $publicFieldIds  Pass the loaded ids when converting many points, see MapPointCategory::publicFieldIds().
     */
    public static function fromModel(MapPoint $model, bool $onlyPublic = true, ?MapPointCategoryTree $tree = null, ?array $publicFieldIds = null): self
    {
        $tree ??= MapPointCategory::tree();
        $publicFieldIds ??= MapPointCategory::publicFieldIds();
        $fieldService = app(MapPointFieldService::class);
        $model->loadMissing(['fields.options', 'fields.formField']);

        $fields = $fieldService->activeFields($model, $tree)
            ->map(fn (MapPointField $field): MapPointFieldValueData => MapPointFieldValueData::fromModel($field, in_array($field->form_field_id, $publicFieldIds, true)))
            ->filter(fn (MapPointFieldValueData $field): bool => ! $onlyPublic || $field->is_public)
            ->values()
            ->toBase();

        $formerFields = $onlyPublic
            ? new Collection
            : $fieldService->formerFields($model, $tree)->map(fn (MapPointField $field): MapPointFieldValueData => MapPointFieldValueData::fromModel($field, false))->toBase();

        return new self(
            id: $model->uuid,
            coordinate: $model->coordinate,
            title: $model->title,
            description: $model->description ?? '',
            published: $model->published,
            userReadablePointableType: self::formatType($model->pointable_type),
            created_at: $model->created_at,
            group_id: $model->group->uuid,
            category_id: $model->category?->uuid,
            location: $model->location,
            fields: $fields,
            former_fields: $formerFields,
        );
    }

    private static function formatType(?string $type): string
    {
        // TODO load from model itself
        if ($type === FormSubmission::class) {
            return 'Formular';
        }
        if ($type === Advice::class) {
            return 'Beratung';
        }

        return 'Manuell';
    }
}
