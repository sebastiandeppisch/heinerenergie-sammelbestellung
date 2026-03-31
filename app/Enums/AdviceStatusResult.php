<?php

declare(strict_types=1);

namespace App\Enums;

use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
enum AdviceStatusResult: int
{
    case New = 0;
    case InProgress = 1;
    case Completed = 2;
    case Unsuccessfully = 3;
}
