<?php

declare(strict_types=1);

namespace App\Imports;

use App\Enums\MapPointSpreadsheetField;
use App\Exceptions\SpreadsheetValueException;
use App\Models\Group;
use App\Models\MapPoint;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Str;

/**
 * Finds the existing map point a spreadsheet row refers to by the chosen key field.
 *
 * Only points of the group and its descendants are matched, so an import never touches other initiatives.
 * Values are compared regardless of case, the way people read them. The points are loaded once, because
 * map point spreadsheets are small.
 */
class MapPointKeyMatcher
{
    /** @var array<string, Collection<int, MapPoint>> */
    private array $pointsByKey = [];

    /** @var array<string, true> */
    private array $seenKeys = [];

    /**
     * @param  MapPointSpreadsheetField  $keyField  IGNORE means no key field, so every row creates a new point.
     */
    public function __construct(public readonly MapPointSpreadsheetField $keyField, Group $group)
    {
        if ($keyField === MapPointSpreadsheetField::IGNORE) {
            return;
        }

        $points = MapPoint::query()->visibleFromGroup($group)->with(['group', 'category'])->get();

        foreach ($points as $point) {
            $key = $this->normalize($this->keyOf($point));

            if ($key !== null) {
                $this->pointsByKey[$key] ??= new Collection;
                $this->pointsByKey[$key]->push($point);
            }
        }
    }

    /**
     * @return MapPoint|null The point the row updates, null when the row creates a new point.
     *
     * @throws SpreadsheetValueException When the key value cannot be matched unambiguously.
     */
    public function find(?string $keyValue): ?MapPoint
    {
        if ($this->keyField === MapPointSpreadsheetField::IGNORE) {
            return null;
        }

        $key = $this->normalize($keyValue);

        if ($key === null) {
            // A row without an id is a new point, for example one added to an exported file.
            return $this->keyField === MapPointSpreadsheetField::ID
                ? null
                : throw new SpreadsheetValueException('Der Wert fehlt, an ihm werden vorhandene Punkte erkannt.', $this->keyField->value);
        }

        if (isset($this->seenKeys[$key])) {
            throw new SpreadsheetValueException('Dieser Wert kommt in der Datei mehrfach vor.', $this->keyField->value);
        }

        $this->seenKeys[$key] = true;
        $matches = $this->pointsByKey[$key] ?? new Collection;

        if ($matches->count() > 1) {
            throw new SpreadsheetValueException('Mehrere Kartenpunkte haben diesen Wert.', $this->keyField->value);
        }

        if ($matches->isNotEmpty()) {
            return $matches->firstOrFail();
        }

        if ($this->keyField !== MapPointSpreadsheetField::ID) {
            return null;
        }

        // Ids are not secret and nobody guesses them, so a hit outside the own initiative means the file
        // was copied from another one. Naming that is more useful than pretending the point does not exist.
        throw new SpreadsheetValueException(Str::isUuid($key) && MapPoint::query()->where('uuid', $key)->exists()
            ? 'Dieser Kartenpunkt gehört zu einer anderen Initiative. Soll er hier neu angelegt werden, stelle die ID-Spalte auf „Ignorieren“.'
            : 'Zu dieser ID gibt es keinen Kartenpunkt.', $this->keyField->value);
    }

    private function keyOf(MapPoint $point): ?string
    {
        return match ($this->keyField) {
            MapPointSpreadsheetField::ID => $point->uuid,
            MapPointSpreadsheetField::TITLE => $point->title,
            MapPointSpreadsheetField::LOCATION => $point->location,
            default => null,
        };
    }

    private function normalize(?string $value): ?string
    {
        $text = SpreadsheetCell::text($value);

        return $text === null ? null : Str::lower($text);
    }
}
