<?php

declare(strict_types=1);

namespace App\Enums;

use Spatie\TypeScriptTransformer\Attributes\TypeScript;

/**
 * The map point field a spreadsheet column is imported into and exported from.
 */
#[TypeScript]
enum MapPointSpreadsheetField: string
{
    /** The column is skipped on import and left out on export. */
    case IGNORE = 'ignore';

    case ID = 'id';

    case TITLE = 'title';

    case DESCRIPTION = 'description';

    case LATITUDE = 'lat';

    case LONGITUDE = 'lng';

    case LOCATION = 'location';

    /** Matched by category name, unknown names create a new category. */
    case CATEGORY = 'category';

    case PUBLISHED = 'published';

    public function label(): string
    {
        return match ($this) {
            self::IGNORE => 'Ignorieren',
            self::ID => 'ID',
            self::TITLE => 'Titel',
            self::DESCRIPTION => 'Beschreibung',
            self::LATITUDE => 'Breitengrad',
            self::LONGITUDE => 'Längengrad',
            self::LOCATION => 'Ort',
            self::CATEGORY => 'Kategorie',
            self::PUBLISHED => 'Veröffentlicht',
        };
    }

    /**
     * Whether the field can identify an existing map point on import.
     */
    public function isKey(): bool
    {
        return in_array($this, self::keys(), true);
    }

    /**
     * @return array<int, self>
     */
    public static function keys(): array
    {
        return [self::ID, self::TITLE, self::LOCATION];
    }

    /**
     * The values an import accepts as its key field. IGNORE means no key at all, so every row
     * creates a new map point. A spreadsheet written by hand usually has no column holding our ids.
     *
     * @return array<int, self>
     */
    public static function keyOptions(): array
    {
        return [self::IGNORE, ...self::keys()];
    }
}
