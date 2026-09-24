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
 */
class MapPointCategoryResolver
{
    /** @var array<string, MapPointCategory> */
    private array $usableByName = [];

    /** @var array<string, MapPointCategory> */
    private array $descendantByName = [];

    /** @var array<int, string> */
    private array $createdNames = [];

    public function __construct(private readonly Group $group)
    {
        $hierarchyIds = $group->getHierarchyIds();

        $usableCategories = MapPointCategory::query()
            ->usableInGroup($group)
            ->get()
            ->sortBy(fn (MapPointCategory $category): int => (int) array_search($category->group_id, $hierarchyIds, true));

        foreach ($usableCategories as $category) {
            $this->usableByName[Str::lower($category->name)] ??= $category;
        }

        $descendantCategories = MapPointCategory::query()
            ->whereIn('group_id', array_diff($group->getSubtreeIds(), [$group->id]))
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

        $category = MapPointCategory::create(['group_id' => $this->group->id, 'name' => $name]);
        $this->usableByName[$key] = $category;
        $this->createdNames[] = $name;

        return $category;
    }

    /**
     * @return array<int, string>
     */
    public function createdNames(): array
    {
        return $this->createdNames;
    }
}
