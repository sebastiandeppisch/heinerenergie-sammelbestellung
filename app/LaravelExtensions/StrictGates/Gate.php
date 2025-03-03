<?php

namespace App\LaravelExtensions\StrictGates;

use Illuminate\Auth\Access\Gate as BaseGate;
use Illuminate\Support\Str;
use Override;

class Gate extends BaseGate
{
    public static $shouldBeStrict = false;

    public static function shouldBeStrict($shouldBeStrict = true)
    {
        self::$shouldBeStrict = $shouldBeStrict;
    }

    /**
     * the implementation is mostly copied from the original function
     * only the MissingGateException is added
     */
    #[Override]
    protected function resolveAuthCallback($user, $ability, array $arguments)
    {
        if (isset($arguments[0]) &&
            ! is_null($policy = $this->getPolicyFor($arguments[0])) &&
            $callback = $this->resolvePolicyCallback($user, $ability, $arguments, $policy)) {
            return $callback;
        }

        if (isset($this->stringCallbacks[$ability])) {
            [$class, $method] = Str::parseCallback($this->stringCallbacks[$ability]);

            if ($this->canBeCalledWithUser($user, $class, $method ?: '__invoke')) {
                return $this->abilities[$ability];
            }
        }

        if (isset($this->abilities[$ability]) &&
            $this->canBeCalledWithUser($user, $this->abilities[$ability])) {
            return $this->abilities[$ability];
        }

        throw new MissingGateException($ability, $arguments);
    }
}
