<?php

declare(strict_types=1);

namespace App\Exceptions;

use RuntimeException;

/**
 * A single value of a spreadsheet row cannot be imported. The message is shown to the user.
 */
class SpreadsheetValueException extends RuntimeException
{
    /**
     * @param  string|null  $field  The field the value is mapped to, used to name its column.
     */
    public function __construct(string $message, public readonly ?string $field = null)
    {
        parent::__construct($message);
    }
}
