<?php

declare(strict_types=1);

namespace App\LaravelExtensions\StrictGates;

use OutOfBoundsException;

class MissingGateException extends OutOfBoundsException
{
    /**
     * @param  array<int, mixed>  $arguments
     */
    public function __construct(string $ability, array $arguments)
    {
        $argumentNames = [];
        foreach ($arguments as $argument) {
            if (is_object($argument)) {
                $argumentNames[] = $argument::class;
            } else {
                $argumentNames[] = gettype($argument);
            }
        }

        parent::__construct("Gate not found: {$ability} with arguments: ".implode(', ', $argumentNames));
    }
}
