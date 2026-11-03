<?php

declare(strict_types=1);

namespace App\Enums;

use Spatie\TypeScriptTransformer\Attributes\TypeScript;

/**
 * What the map point target of a form does: create a point, or add to the point a manage link belongs to.
 */
#[TypeScript]
enum MapPointTargetMode: string
{
    case CREATE = 'create';
    case UPDATE = 'update';
}
