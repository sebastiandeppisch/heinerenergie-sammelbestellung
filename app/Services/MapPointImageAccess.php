<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\FieldType;
use App\Models\MapPoint;
use App\Models\MapPointCategory;
use App\Models\MapPointField;
use App\Models\User;
use App\ValueObjects\MapPointCategoryTree;
use Illuminate\Support\Facades\Gate;

/**
 * Decides who may see an image of a map point. It is checked on every request, so making a field private
 * takes effect at once.
 */
class MapPointImageAccess
{
    private ?MapPointCategoryTree $tree = null;

    /** @var array<int, int>|null */
    private ?array $publicFieldIds = null;

    public function __construct(
        private readonly MapPointFieldService $fieldService,
        private readonly PublicMapPointPresenter $presenter,
    ) {}

    public static function url(MapPoint $mapPoint, string $path): string
    {
        return route('map-point-images.show', [$mapPoint, basename($path)]);
    }

    public function allows(?User $user, MapPoint $mapPoint, string $path): bool
    {
        $mapPoint->loadMissing('fields.formField');
        $field = $mapPoint->fields->first(fn (MapPointField $field): bool => $field->type === FieldType::IMAGE && in_array($path, (array) $field->value, true));

        if ($field === null) {
            return false;
        }

        return ($user !== null && Gate::forUser($user)->allows('update', $mapPoint)) || $this->isPublic($mapPoint, $field);
    }

    /**
     * The same rule as for the public map, see PublicMapPointPresenter: a visible point, a value of one of the point's
     * current fields, and the field is public and consented to. Needs the relation fields.formField. The categories and public fields are loaded
     * once per instance, which lives for one request, so a field made private is hidden with the next request.
     */
    public function isPublic(MapPoint $mapPoint, MapPointField $field): bool
    {
        if (! $this->presenter->isVisible($mapPoint)) {
            return false;
        }

        $this->publicFieldIds ??= MapPointCategory::publicFieldIds();

        if (! $this->presenter->isFieldPublic($mapPoint, $field, $this->publicFieldIds)) {
            return false;
        }

        $this->tree ??= MapPointCategory::tree();

        return $this->fieldService->activeFields($mapPoint, $this->tree)->contains('id', $field->id);
    }
}
