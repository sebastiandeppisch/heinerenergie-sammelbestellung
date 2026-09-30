<?php

declare(strict_types=1);

namespace App\Imports;

use App\Enums\MapPointSpreadsheetField;
use App\Exceptions\SpreadsheetValueException;
use App\Models\Group;
use App\Models\MapPointCategory;
use Illuminate\Support\Str;

/**
 * Resolves category names from a spreadsheet to categories usable in the group, regardless of case.
 *
 * When a name exists on several levels of the hierarchy, the category of the nearest group wins.
 * Unknown names create a category in the group. A name only a sub initiative uses is refused instead,
 * because that sub initiative would then see two categories with the same name.
 *
 * With a main category, names are sub categories of it: they are looked up below the main category only, and
 * unknown names create a sub category of it, which uses its fields. The same name below another category does
 * not matter then.
 */
class MapPointCategoryResolver
{
    /** @var array<string, MapPointCategory> */
    private array $usableByName = [];

    /** @var array<string, MapPointCategory> */
    private array $descendantByName = [];

    /** @var array<int, string> */
    private array $createdNames = [];

    public function __construct(private readonly Group $group, private readonly ?MapPointCategory $mainCategory = null)
    {
        $hierarchyIds = $group->getHierarchyIds();
        $tree = MapPointCategory::tree();
        $mainSubtreeIds = $mainCategory === null ? null : $tree->subtreeIds([$mainCategory->id]);

        $usableCategories = MapPointCategory::query()
            ->usableInGroup($group)
            ->when($mainSubtreeIds !== null, fn ($query) => $query->whereIn('id', $mainSubtreeIds))
            ->get()
            // The main category and the levels right below it win over deeper sub categories with the same name.
            ->sortBy(fn (MapPointCategory $category): int => $mainCategory !== null
                ? count($tree->ancestorIds($category->id))
                : (int) array_search($category->group_id, $hierarchyIds, true));

        foreach ($usableCategories as $category) {
            $this->usableByName[Str::lower($category->name)] ??= $category;
        }

        $descendantCategories = MapPointCategory::query()
            ->whereIn('group_id', array_diff($group->getSubtreeIds(), [$group->id]))
            ->when($mainSubtreeIds !== null, fn ($query) => $query->whereIn('id', $mainSubtreeIds))
            ->with('group')
            ->get();

        foreach ($descendantCategories as $category) {
            $this->descendantByName[Str::lower($category->name)] ??= $category;
        }
    }

    /**
     * @throws SpreadsheetValueException When only a sub initiative has a category with this name.
     */
    public function resolve(string $name): MapPointCategory
    {
        $key = Str::lower($name);

        if (isset($this->usableByName[$key])) {
            return $this->usableByName[$key];
        }

        if (isset($this->descendantByName[$key])) {
            throw new SpreadsheetValueException(sprintf(
                'Die Kategorie „%s“ gibt es nur in der Unterinitiative „%s“. Klärt bitte untereinander, ob sie in diese Initiative verschoben oder umbenannt wird.',
                $this->descendantByName[$key]->name,
                $this->descendantByName[$key]->group->name,
            ), MapPointSpreadsheetField::CATEGORY->value);
        }

        $category = MapPointCategory::create(['group_id' => $this->group->id, 'name' => $name, 'parent_id' => $this->mainCategory?->id]);
        $this->usableByName[$key] = $category;
        $this->createdNames[] = $name;

        return $category;
    }

    /**
     * A category the import creates itself, e.g. a new main category, is reported like the ones created for rows.
     */
    public function reportCreated(MapPointCategory $category): void
    {
        $this->createdNames[] = $category->name;
    }

    /**
     * @return array<int, string>
     */
    public function createdNames(): array
    {
        return $this->createdNames;
    }
}
