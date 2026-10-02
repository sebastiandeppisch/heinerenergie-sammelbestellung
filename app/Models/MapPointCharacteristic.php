<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Traits\HasMapPointFields;
use App\Models\Traits\HasUuid;
use App\ValueObjects\MapPointCategoryTree;
use Database\Factories\MapPointCharacteristicFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Override;

/**
 * An optional extra of a point, called "Maßnahme" in the UI, e.g. a hedgehog gate. It belongs to one category and
 * can be chosen for points of that category and all its sub categories. Its fields are added to the category fields.
 *
 * @property int $id
 * @property string $uuid
 * @property int $map_point_category_id
 * @property string $name
 * @property string|null $icon_path
 * @property string|null $color
 * @property int $sort_order
 */
class MapPointCharacteristic extends Model
{
    /** @use HasFactory<MapPointCharacteristicFactory> */
    use HasFactory;

    use HasMapPointFields;
    use HasUuid;

    protected $fillable = [
        'map_point_category_id',
        'name',
        'icon_path',
        'color',
        'sort_order',
    ];

    /**
     * @return BelongsTo<MapPointCategory, $this>
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(MapPointCategory::class, 'map_point_category_id');
    }

    /**
     * @return BelongsToMany<MapPoint, $this>
     */
    public function mapPoints(): BelongsToMany
    {
        return $this->belongsToMany(MapPoint::class, 'map_point_characteristic_map_point')->withTimestamps();
    }

    /**
     * The characteristics points of the category can have: those of the category and of its ancestors.
     *
     * @return Builder<MapPointCharacteristic>
     */
    public static function selectableFor(?int $categoryId, ?MapPointCategoryTree $tree = null): Builder
    {
        $categoryIds = $categoryId === null ? [] : [$categoryId, ...($tree ?? MapPointCategory::tree())->ancestorIds($categoryId)];

        return self::query()->whereIn('map_point_category_id', $categoryIds)->orderBy('sort_order');
    }

    public function isSelectableFor(?int $categoryId, ?MapPointCategoryTree $tree = null): bool
    {
        return $categoryId !== null
            && in_array($this->map_point_category_id, [$categoryId, ...($tree ?? MapPointCategory::tree())->ancestorIds($categoryId)], true);
    }

    protected function formDefinitionName(): string
    {
        return 'Felder der Maßnahme '.$this->name;
    }

    protected function fieldsGroupId(): int
    {
        return $this->category->group_id;
    }

    /**
     * Points lose the characteristic. The fields are deleted, their values stay on the points as former values.
     */
    #[Override]
    public function delete(): ?bool
    {
        $isDeleted = DB::transaction(function (): ?bool {
            $this->mapPoints()->detach();
            FormDefinitionToMapPointCharacteristic::where('map_point_characteristic_id', $this->id)->delete();

            $formDefinition = $this->formDefinition;
            $isDeleted = parent::delete();
            $formDefinition?->delete();

            return $isDeleted;
        });

        if ($this->icon_path) {
            Storage::disk('public')->delete($this->icon_path);
        }

        return $isDeleted;
    }
}
