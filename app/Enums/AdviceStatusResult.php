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

    /**
     * Name of the status which is created for every new group. More fine
     * grained statuses can be defined by the group itself later on.
     */
    public function defaultStatusName(): string
    {
        return match ($this) {
            self::New => 'Offen',
            self::InProgress => 'In Bearbeitung',
            self::Completed => 'Fertig - erfolgreich',
            self::Unsuccessfully => 'Fertig - nicht erfolgreich',
        };
    }
}
