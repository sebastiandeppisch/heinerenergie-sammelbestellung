<?php

namespace App\LaravelExtensions\StrictGates;

use OutOfBoundsException;

class MissingGateException extends OutOfBoundsException
{
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
