<?php

declare(strict_types=1);

namespace App\Enums;

use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
enum FormType: int
{
    case Form = 0;
    case Checklist = 1;

    /** The additional fields of a map point category. It is never filled in as a form, points store the values. */
    case MapPointFields = 2;
}
