<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\FieldType;
use App\Models\MapPoint;
use App\Models\MapPointCategory;
use App\Models\MapPointField;
use App\Models\User;
use Illuminate\Support\Facades\Gate;

/**
 * Decides who may see an image of a map point. It is checked on every request, so making a field private
 * takes effect at once.
 */
class MapPointImageAccess
{
    public function __construct(private readonly MapPointFieldService $fieldService) {}

    public static function url(MapPoint $mapPoint, string $path): string
    {
        return route('map-point-images.show', [$mapPoint, basename($path)]);
    }

    public function allows(?User $user, MapPoint $mapPoint, string $path): bool
    {
        $field = $mapPoint->fields()
            ->with('formField')
            ->where('type', FieldType::IMAGE)
            ->get()
            ->first(fn (MapPointField $field): bool => in_array($path, (array) $field->value, true));

        if ($field === null) {
            return false;
        }

        return ($user !== null && Gate::forUser($user)->allows('update', $mapPoint)) || $this->isPublic($mapPoint, $field);
    }

    /**
     * The same rule as for the public map: a published point, a value of one of the point's current fields,
     * and the field is public.
     */
    public function isPublic(MapPoint $mapPoint, MapPointField $field): bool
    {
        if (! $mapPoint->published || ! in_array($field->form_field_id, MapPointCategory::publicFieldIds(), true)) {
            return false;
        }

        return $this->fieldService->activeFields($mapPoint, MapPointCategory::tree())->contains('id', $field->id);
    }
}
