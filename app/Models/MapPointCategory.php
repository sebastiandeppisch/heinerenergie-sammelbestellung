<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\FormType;
use App\Models\Traits\HasUuid;
use App\ValueObjects\MapPointCategoryTree;
use Database\Factories\MapPointCategoryFactory;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Override;

/**
 * @property int $group_id
 * @property int|null $parent_id
 * @property int|null $form_definition_id
 */
class MapPointCategory extends Model
{
    /** @use HasFactory<MapPointCategoryFactory> */
    use HasFactory;

    use HasUuid;

    protected $fillable = [
        'group_id',
        'parent_id',
        'name',
        'image_path',
    ];

    /**
     * @return HasMany<MapPoint, $this>
     */
    public function mapPoints(): HasMany
    {
        return $this->hasMany(MapPoint::class, 'category_id');
    }

    /**
     * @return BelongsTo<MapPointCategory, $this>
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(MapPointCategory::class, 'parent_id');
    }

    /**
     * @return HasMany<MapPointCategory, $this>
     */
    public function children(): HasMany
    {
        return $this->hasMany(MapPointCategory::class, 'parent_id');
    }

    /**
     * @return BelongsToMany<MapEmbed, $this>
     */
    public function mapEmbeds(): BelongsToMany
    {
        return $this->belongsToMany(MapEmbed::class);
    }

    /**
     * The additional fields of this category's points. Sub categories inherit them.
     *
     * @return BelongsTo<FormDefinition, $this>
     */
    public function formDefinition(): BelongsTo
    {
        return $this->belongsTo(FormDefinition::class);
    }

    /**
     * The own fields that are shown on the public map. All other fields stay internal.
     *
     * @return Builder<FormField>
     */
    public function publicFields(): Builder
    {
        return FormField::query()
            ->where('form_definition_id', $this->form_definition_id)
            ->whereIn('id', DB::table('map_point_public_fields')->select('form_field_id'));
    }

    /**
     * Marks exactly the given own fields as public. The caller ensures the fields belong to the category.
     *
     * @param  array<int, int>  $formFieldIds
     */
    public function syncPublicFields(array $formFieldIds): void
    {
        DB::transaction(function () use ($formFieldIds): void {
            DB::table('map_point_public_fields')
                ->whereIn('form_field_id', FormField::query()->where('form_definition_id', $this->form_definition_id)->select('id'))
                ->delete();

            DB::table('map_point_public_fields')->insert(array_map(fn (int $formFieldId): array => [
                'form_field_id' => $formFieldId,
                'created_at' => now(),
                'updated_at' => now(),
            ], $formFieldIds));
        });
    }

    /**
     * The form definition holding the category's fields, created on first use.
     */
    public function findOrCreateFormDefinition(): FormDefinition
    {
        if ($this->formDefinition !== null) {
            return $this->formDefinition;
        }

        $formDefinition = FormDefinition::create([
            'name' => 'Felder der Kategorie '.$this->name,
            'group_id' => $this->group_id,
            'type' => FormType::MapPointFields,
            'is_active' => true,
        ]);

        $this->formDefinition()->associate($formDefinition)->save();

        return $formDefinition;
    }

    /**
     * @return BelongsTo<Group, $this>
     */
    public function group(): BelongsTo
    {
        return $this->belongsTo(Group::class);
    }

    /**
     * Categories are shared down the hierarchy: a group may use its own
     * categories and the ones of all its ancestors.
     *
     * @param  Builder<MapPointCategory>  $query
     */
    #[Scope]
    protected function usableInGroup(Builder $query, Group $group): void
    {
        $query->whereIn('group_id', $group->getHierarchyIds());
    }

    /**
     * Loads the categories of all groups as tree. That is fine because there are only few categories.
     */
    public static function tree(): MapPointCategoryTree
    {
        $categories = self::query()->get(['id', 'uuid', 'parent_id', 'image_path', 'form_definition_id']);
        $formDefinitionIdsWithFields = FormField::query()
            ->whereIn('form_definition_id', $categories->pluck('form_definition_id')->filter())
            ->distinct()
            ->pluck('form_definition_id')
            ->map(fn (mixed $id): int => (int) $id)
            ->all();

        return MapPointCategoryTree::fromCategories($categories, $formDefinitionIdsWithFields);
    }

    /**
     * The ids of all fields shown on the public map. Loaded at once, because there are only few categories.
     *
     * @return array<int, int>
     */
    public static function publicFieldIds(): array
    {
        return DB::table('map_point_public_fields')->pluck('form_field_id')->map(fn (mixed $id): int => (int) $id)->all();
    }

    public function isUsableInGroup(Group $group): bool
    {
        return in_array($this->group_id, $group->getHierarchyIds(), true);
    }

    /**
     * The map of a group shows the points of the group and its descendants. A category can
     * therefore appear on it when it belongs to the group, an ancestor or a descendant.
     */
    public function isAvailableOnMapOfGroup(Group $group): bool
    {
        return in_array($this->group_id, [...$group->getHierarchyIds(), ...$group->getSubtreeIds()], true);
    }

    /**
     * Sub categories and points move up to the parent, so nothing is lost. The category's own fields
     * are deleted, their values stay on the points as former values. The parent's group is
     * an ancestor of this category's group, so they may still use it. Embeds showing this category
     * keep showing its sub categories.
     */
    #[Override]
    public function delete(): ?bool
    {
        $isDeleted = DB::transaction(function (): ?bool {
            $childIds = $this->children()->pluck('id')->all();

            foreach ($this->mapEmbeds as $mapEmbed) {
                $mapEmbed->mapPointCategories()->syncWithoutDetaching($childIds);
            }

            $this->children()->update(['parent_id' => $this->parent_id]);
            $this->mapPoints()->update(['category_id' => $this->parent_id]);

            // Forms create their points in the parent from now on, like the points of this category moved there.
            FormDefinitionToMapPoint::where('map_point_category_id', $this->id)->update(['map_point_category_id' => $this->parent_id]);
            FormDefinitionToMapPointSubcategory::where('map_point_category_id', $this->id)->delete();

            $formDefinition = $this->formDefinition;
            $isDeleted = parent::delete();

            // Values of the deleted fields stay on the points as former values.
            $formDefinition?->delete();

            return $isDeleted;
        });

        if ($this->image_path) {
            Storage::disk('public')->delete($this->image_path);
        }

        return $isDeleted;
    }
}
