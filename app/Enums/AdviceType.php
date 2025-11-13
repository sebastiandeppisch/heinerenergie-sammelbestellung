<?php

namespace App\Enums;

enum AdviceType: int
{
    case Home = 0;
    case Virtual = 1;
    case DirectOrder = 2;

    public static function getSelectableOptions(?self $currentType = null): array
    {
        return collect(self::cases())
            ->filter(function (self $type) use ($currentType) {
                if ($type === self::Home || $type === self::Virtual) {
                    return true;
                }
                // Only include DirectOrder if the advice already has this type
                return $type === self::DirectOrder && $currentType === self::DirectOrder;
            })
            ->map(fn (self $type, int $key) => [
                'id' => $type->value,
                'name' => $type->name,
            ])
            ->values()
            ->toArray();
    }
}
