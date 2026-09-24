<?php

declare(strict_types=1);

namespace App\ValueObjects;

/**
 * An uploaded spreadsheet kept until it is imported.
 */
readonly class SpreadsheetUpload
{
    public function __construct(
        public string $token,
        public string $path,
        public string $filename,
    ) {}
}
