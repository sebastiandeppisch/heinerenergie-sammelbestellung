<?php

declare(strict_types=1);

namespace App\Enums;

use Illuminate\Support\Str;
use Maatwebsite\Excel\Excel;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

/**
 * The spreadsheet file formats map points can be imported from and exported to.
 */
#[TypeScript]
enum SpreadsheetFormat: string
{
    case XLSX = 'xlsx';

    case ODS = 'ods';

    case XLS = 'xls';

    case CSV = 'csv';

    public function label(): string
    {
        return match ($this) {
            self::XLSX => 'Excel (.xlsx)',
            self::ODS => 'OpenDocument (.ods)',
            self::XLS => 'Excel 97-2003 (.xls)',
            self::CSV => 'CSV (.csv)',
        };
    }

    /**
     * The reader and writer type Laravel Excel expects for this format.
     */
    public function excelType(): string
    {
        return match ($this) {
            self::XLSX => Excel::XLSX,
            self::ODS => Excel::ODS,
            self::XLS => Excel::XLS,
            self::CSV => Excel::CSV,
        };
    }

    public static function fromFilename(string $filename): ?self
    {
        $extension = Str::lower(pathinfo($filename, PATHINFO_EXTENSION));

        return $extension === 'txt' ? self::CSV : self::tryFrom($extension);
    }
}
