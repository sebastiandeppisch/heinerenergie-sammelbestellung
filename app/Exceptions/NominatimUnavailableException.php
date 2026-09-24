<?php

declare(strict_types=1);

namespace App\Exceptions;

use Exception;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

/**
 * The geocoding service of OpenStreetMap could not be reached.
 *
 * This is deliberately distinct from "address not found", which the geocoding
 * actions express by returning null. Only this exception may be retried.
 */
class NominatimUnavailableException extends Exception
{
    public static function requestFailed(Throwable $previous): self
    {
        return new self(
            'Der Adressdienst von OpenStreetMap ist gerade nicht erreichbar. Bitte versuche es später erneut.',
            previous: $previous
        );
    }

    public static function tooManyPendingRequests(): self
    {
        return new self('Es warten gerade zu viele Adressabfragen. Bitte versuche es in einer Minute erneut.');
    }

    public function render(Request $request): Response
    {
        if ($request->header('X-Inertia')) {
            return back()->withErrors(['geocoding' => $this->getMessage()]);
        }

        return response()->json(['message' => $this->getMessage()], Response::HTTP_SERVICE_UNAVAILABLE);
    }
}
