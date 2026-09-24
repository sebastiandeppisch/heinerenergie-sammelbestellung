<?php

declare(strict_types=1);

namespace App\Data;

use App\Imports\SpreadsheetCell;
use App\ValueObjects\SpreadsheetContent;
use App\ValueObjects\SpreadsheetUpload;
use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

/**
 * An uploaded spreadsheet waiting to be mapped and imported.
 */
#[TypeScript]
class SpreadsheetUploadData extends Data
{
    /**
     * @param  array<int, string>  $headers
     * @param  array<int, array<int, string|null>>  $preview_rows  The first data rows, to help choosing the mapping.
     */
    public function __construct(
        public string $token,
        public string $filename,
        public array $headers,
        public array $preview_rows,
        public int $row_count,
    ) {}

    public static function fromContent(SpreadsheetUpload $upload, SpreadsheetContent $content, int $previewRowCount): self
    {
        return new self(
            token: $upload->token,
            filename: $upload->filename,
            headers: $content->headers,
            preview_rows: array_map(
                fn (array $cells): array => array_map(fn (mixed $cell): ?string => SpreadsheetCell::text($cell), $cells),
                array_values(array_slice($content->rows, 0, $previewRowCount)),
            ),
            row_count: count($content->rows),
        );
    }
}
