<?php

namespace App\Http\Middleware;

use App\Exceptions\EncryptionKeyMissing;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ResolveEncryptionKey
{
    /**
     * @param  Closure(Request): (Response)  $next
     *
     * @throws EncryptionKeyMissing
     */
    public function handle(Request $request, Closure $next): Response
    {
        $cookieValue = $request->cookie('enc_key');

        if (! $cookieValue) {
            throw new EncryptionKeyMissing;
        }

        $key = base64_decode($cookieValue);
        app()->instance('user.enc_key', $key);

        return $next($request);
    }
}
