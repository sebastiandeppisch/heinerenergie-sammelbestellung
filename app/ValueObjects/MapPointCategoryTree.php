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
     * @param  array<int, array{uuid: string, parent_id: int|null, image_path: string|null}>  $categoriesById
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
     */
    public static function fromCategories(Collection $categories): self
    {
        $categoriesById = [];

        foreach ($categories as $category) {
            $categoriesById[$category->id] = [
                'uuid' => $category->uuid,
                'parent_id' => $category->parent_id,
                'image_path' => $category->image_path,
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
        foreach ([$categoryId, ...$this->ancestorIds($categoryId)] as $id) {
            if (($this->categoriesById[$id]['image_path'] ?? null) !== null) {
                return $this->categoriesById[$id]['image_path'];
            }
        }

        return null;
    }
}
