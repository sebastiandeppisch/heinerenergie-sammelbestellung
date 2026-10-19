<?php

declare(strict_types=1);

namespace App\Services;

use App\Data\MapPointData;
use App\Data\MapPointFieldValueData;
use App\Enums\PublicConsent;
use App\Models\MapPoint;
use App\Models\MapPointCategory;
use App\Models\MapPointField;
use App\ValueObjects\MapPointCategoryTree;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

/**
 * Decides what of a map point the public sees. Every public output uses it: the public map with its table,
 * the embed preview and the images. A point is shown when the admin published it and the person did not refuse.
 * With a given consent a detail is shown only when it is public now and its key was consented to, so a field made
 * public later stays private for existing consents. Marker, category and characteristics are always shown.
 */
class PublicMapPointPresenter
{
    /**
     * @template TQuery of Builder<MapPoint>
     *
     * @param  TQuery  $query
     * @return TQuery
     */
    public function scopeVisible(Builder $query): Builder
    {
        return $query->where('published', true)->where('public_consent', '!=', PublicConsent::REFUSED->value);
    }

    public function isVisible(MapPoint $mapPoint): bool
    {
        return $mapPoint->published && $mapPoint->public_consent !== PublicConsent::REFUSED;
    }

    public function allowsKey(MapPoint $mapPoint, string $key): bool
    {
        return match ($mapPoint->public_consent) {
            PublicConsent::NOT_REQUIRED => true,
            PublicConsent::GIVEN => in_array($key, $mapPoint->public_consent_fields ?? [], true),
            PublicConsent::REFUSED => false,
        };
    }

    /**
     * Whether the value is public by its field and by the consent. Needs the relation formField.
     *
     * @param  array<int, int>  $publicFieldIds  see MapPointCategory::publicFieldIds()
     */
    public function isFieldPublic(MapPoint $mapPoint, MapPointField $field, array $publicFieldIds): bool
    {
        return $field->formField !== null
            && in_array($field->form_field_id, $publicFieldIds, true)
            && $this->allowsKey($mapPoint, MapPointConsentService::fieldKey($field->formField->uuid));
    }

    /**
     * The visible points of the query as the public sees them.
     *
     * @param  Builder<MapPoint>  $query
     * @return Collection<int, MapPointData>
     */
    public function presentAll(Builder $query, ?MapPointCategoryTree $tree = null): Collection
    {
        $tree ??= MapPointCategory::tree();
        $publicFieldIds = MapPointCategory::publicFieldIds();

        return $this->scopeVisible($query)
            ->with(['category', 'group', 'characteristics.category', 'fields.options', 'fields.formField'])
            ->get()
            ->map(fn (MapPoint $mapPoint): MapPointData => $this->present($mapPoint, $tree, $publicFieldIds))
            ->toBase();
    }

    /**
     * The point as the public sees it. Does not check whether it is visible, see isVisible().
     *
     * @param  array<int, int>|null  $publicFieldIds
     */
    public function present(MapPoint $mapPoint, ?MapPointCategoryTree $tree = null, ?array $publicFieldIds = null): MapPointData
    {
        $data = MapPointData::fromModel($mapPoint, onlyPublic: true, tree: $tree, publicFieldIds: $publicFieldIds);

        $data->title = $this->allowsKey($mapPoint, MapPointConsentService::TITLE) ? $data->title : ($mapPoint->category?->name ?? '');
        $data->description = $this->allowsKey($mapPoint, MapPointConsentService::DESCRIPTION) ? $data->description : '';
        $data->location = $this->allowsKey($mapPoint, MapPointConsentService::LOCATION) ? $data->location : null;
        $data->fields = $data->fields
            ->filter(fn (MapPointFieldValueData $field): bool => $field->field_id !== null && $this->allowsKey($mapPoint, MapPointConsentService::fieldKey($field->field_id)))
            ->values();

        return $data;
    }
}
