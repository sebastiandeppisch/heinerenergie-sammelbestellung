<?php

declare(strict_types=1);

namespace App\Support;

/**
 * Only a keyed hash of a token sent by mail is stored, so the database alone is not enough to use it.
 */
final class TokenHash
{
    public static function make(string $token): string
    {
        return hash_hmac('sha256', $token, (string) config('app.key'));
    }
}
