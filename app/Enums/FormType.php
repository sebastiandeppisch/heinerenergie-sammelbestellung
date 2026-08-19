<?php

declare(strict_types=1);

namespace App\Enums;

use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
enum FormType: int
{
    case Form = 0;
    case Checklist = 1;
}
