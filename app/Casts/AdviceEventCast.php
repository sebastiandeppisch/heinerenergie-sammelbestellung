<?php

namespace App\Casts;

use App\Events\Advice\AdviceEventContract;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;
use InvalidArgumentException;
use Throwable;

class AdviceEventCast implements CastsAttributes
{
    /**
     * Cast the given value.
     *
     * @param  array<string, mixed>  $attributes
     */
    public function get(Model $model, string $key, mixed $value, array $attributes): AdviceEventContract
    {
        $event = unserialize($value);

        if ($event instanceof AdviceEventContract) {
            return $event;
        }

        return $this->errorEvent();

    }

    private function errorEvent(): AdviceEventContract
    {
        return new class implements AdviceEventContract
        {
            public function getDescription(): string
            {
                return 'Fehlerhafte Event-Klasse';
            }
        };
    }

    /**
     * Prepare the given value for storage.
     *
     * @param  array<string, mixed>  $attributes
     */
    public function set(Model $model, string $key, mixed $value, array $attributes): string
    {
        if (! $value instanceof AdviceEventContract) {
            throw new InvalidArgumentException('Invalid event class');
        }

        try {
            return serialize($value);
        } catch (Throwable) {
            return '';
        }
    }
}
