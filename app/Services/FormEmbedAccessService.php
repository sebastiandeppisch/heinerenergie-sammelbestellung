<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\FormDefinition;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;

class FormEmbedAccessService
{
    private const int TOKEN_TTL_MINUTES = 30;

    /**
     * Determine whether the current request is allowed to load the given form.
     *
     * Only restricts requests that browsers identify as being loaded inside an
     * <iframe> (via the `Sec-Fetch-Site`/`Sec-Fetch-Dest` headers). Direct
     * navigations (or browsers that don't send the header) are always allowed.
     */
    public function isEmbedAllowed(FormDefinition $formDefinition, Request $request): bool
    {
        if ($request->header('Sec-Fetch-Dest') !== 'iframe') {
            return true;
        }

        $refererHost = parse_url((string) $request->headers->get('referer'), PHP_URL_HOST);

        if (! $refererHost) {
            return false;
        }

        $allowedDomains = $formDefinition->allowed_embed_domains ?? [];

        return collect($allowedDomains)
            ->contains(fn (string $domain): bool => strcasecmp($domain, $refererHost) === 0);
    }

    /**
     * Issue a short-lived, stateless token proving the form was recently loaded.
     * Not a substitute for real CSRF protection (no session binding) — it only
     * raises the bar against naive spam bots that submit without loading the form.
     */
    public function issueToken(FormDefinition $formDefinition): string
    {
        return Crypt::encrypt([
            'uuid' => $formDefinition->uuid,
            'expires' => now()->addMinutes(self::TOKEN_TTL_MINUTES)->timestamp,
        ]);
    }

    public function verifyToken(FormDefinition $formDefinition, ?string $token): bool
    {
        if (! $token) {
            return false;
        }

        try {
            $payload = Crypt::decrypt($token);
        } catch (DecryptException) {
            return false;
        }

        return ($payload['uuid'] ?? null) === $formDefinition->uuid
            && ($payload['expires'] ?? 0) >= now()->timestamp;
    }
}
