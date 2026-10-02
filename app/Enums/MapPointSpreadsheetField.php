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

    /** A field of a form definition, e.g. of a map point category. The column names the field with its form_field_id. */
    case FIELD = 'field';

    /**
     * Whether the point has a characteristic ("Maßnahme"), 1 or 0. The column names the characteristic with its
     * characteristic_id. Only offered for one main category, whose characteristics are known.
     */
    case CHARACTERISTIC = 'characteristic';

    /**
     * A category field the import creates in the main category, named after the column. Only used for an import run,
     * a saved template would create it again on every import.
     */
    case NEW_CATEGORY_FIELD = 'new_category_field';

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
            self::FIELD => 'Zusatzfeld',
            self::CHARACTERISTIC => 'Maßnahme',
            self::NEW_CATEGORY_FIELD => 'Neues Zusatzfeld',
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

    /**
     * The types a new category field can get in an import. Fields with options are set up in the form builder.
     *
     * @return array<int, FieldType>
     */
    public static function newFieldTypes(): array
    {
        return [FieldType::TEXT, FieldType::NUMBER, FieldType::DATE];
    }

    /**
     * The fields every point has. Fields and characteristics are offered one by one instead.
     *
     * @return array<int, self>
     */
    public static function pointFields(): array
    {
        return array_values(array_filter(self::cases(), fn (self $field): bool => ! in_array($field, [self::FIELD, self::CHARACTERISTIC, self::NEW_CATEGORY_FIELD], true)));
    }
}
