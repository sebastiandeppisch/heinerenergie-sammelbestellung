<?php

declare(strict_types=1);

namespace App\ValueObjects;

use App\Models\MapPointCategory;
use Illuminate\Support\Collection;

/**
 * The category tree held in memory, so walking up or down the tree needs no query per level.
 */
readonly class MapPointCategoryTree
{
    /** @var array<int, array<int, int>> */
    private array $childIdsById;

    /**
     * @param  array<int, array{uuid: string, parent_id: int|null, image_path: string|null, form_definition_id: int|null}>  $categoriesById  form_definition_id is only set when the category has fields
     */
    public function __construct(private array $categoriesById)
    {
        $childIdsById = [];

        foreach ($categoriesById as $id => $category) {
            if ($category['parent_id'] !== null) {
                $childIdsById[$category['parent_id']][] = $id;
            }
        }

        $this->childIdsById = $childIdsById;
    }

    /**
     * @param  Collection<int, MapPointCategory>  $categories
     * @param  array<int, int>  $formDefinitionIdsWithFields  form definitions that have at least one field
     */
    public static function fromCategories(Collection $categories, array $formDefinitionIdsWithFields = []): self
    {
        $categoriesById = [];

        foreach ($categories as $category) {
            $categoriesById[$category->id] = [
                'uuid' => $category->uuid,
                'parent_id' => $category->parent_id,
                'image_path' => $category->image_path,
                'form_definition_id' => in_array($category->form_definition_id, $formDefinitionIdsWithFields, true) ? $category->form_definition_id : null,
            ];
        }

        return new self($categoriesById);
    }

    public function parentUuid(int $categoryId): ?string
    {
        $parentId = $this->categoriesById[$categoryId]['parent_id'] ?? null;

        return $parentId === null ? null : ($this->categoriesById[$parentId]['uuid'] ?? null);
    }

    /**
     * Categories created after the tree was loaded, e.g. by an import, are unknown to it.
     */
    public function contains(int $categoryId): bool
    {
        return isset($this->categoriesById[$categoryId]);
    }

    /**
     * The ids of the parent, grandparent and so on, nearest first.
     *
     * @return array<int, int>
     */
    public function ancestorIds(int $categoryId): array
    {
        $ancestorIds = [];
        $currentId = $this->categoriesById[$categoryId]['parent_id'] ?? null;

        while ($currentId !== null && $currentId !== $categoryId && ! in_array($currentId, $ancestorIds, true)) {
            $ancestorIds[] = $currentId;
            $currentId = $this->categoriesById[$currentId]['parent_id'] ?? null;
        }

        return $ancestorIds;
    }

    /**
     * @return array<int, int>
     */
    public function descendantIds(int $categoryId): array
    {
        $descendantIds = [];
        $queue = $this->childIdsById[$categoryId] ?? [];

        while ($queue !== []) {
            $currentId = array_shift($queue);

            if ($currentId === $categoryId || in_array($currentId, $descendantIds, true)) {
                continue;
            }

            $descendantIds[] = $currentId;
            $queue = [...$queue, ...$this->childIdsById[$currentId] ?? []];
        }

        return $descendantIds;
    }

    /**
     * The given categories together with all of their descendants.
     *
     * @param  array<int, int>  $categoryIds
     * @return array<int, int>
     */
    public function subtreeIds(array $categoryIds): array
    {
        $subtreeIds = [];

        foreach ($categoryIds as $categoryId) {
            $subtreeIds = [...$subtreeIds, $categoryId, ...$this->descendantIds($categoryId)];
        }

        return array_values(array_unique($subtreeIds));
    }

    /**
     * The image shown as marker: the category's own image or else the one of its nearest ancestor with an image.
     */
    public function markerImagePath(int $categoryId): ?string
    {
        $id = $this->nearestCategoryWith($categoryId, 'image_path');

        return $id === null ? null : $this->categoriesById[$id]['image_path'];
    }

    /**
     * The category whose fields the points of this category have: the category itself if it has fields, or
     * else its nearest ancestor with fields. The fields are never merged, own fields replace inherited ones.
     */
    public function fieldsCategoryId(int $categoryId): ?int
    {
        return $this->nearestCategoryWith($categoryId, 'form_definition_id');
    }

    /**
     * The form definition holding the fields of this category's points, see fieldsCategoryId().
     */
    public function fieldsFormDefinitionId(int $categoryId): ?int
    {
        $id = $this->fieldsCategoryId($categoryId);

        return $id === null ? null : $this->categoriesById[$id]['form_definition_id'];
    }

    /**
     * @param  'image_path'|'form_definition_id'  $key
     */
    private function nearestCategoryWith(int $categoryId, string $key): ?int
    {
        foreach ([$categoryId, ...$this->ancestorIds($categoryId)] as $id) {
            if (($this->categoriesById[$id][$key] ?? null) !== null) {
                return $id;
            }
        }

        return null;
    }
}
