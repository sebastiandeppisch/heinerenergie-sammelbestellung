<?php

declare(strict_types=1);

namespace App\Exceptions;

use Exception;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class PostalCodeAreaException extends Exception
{
    public static function notFound(string $postalCode): self
    {
        return new self(sprintf('Für die Postleitzahl %s wurde kein Gebiet in OpenStreetMap gefunden.', $postalCode));
    }

    public static function notAdjacent(): self
    {
        return new self('Die Postleitzahlgebiete ergeben kein zusammenhängendes Gebiet. Bitte gib nur aneinandergrenzende Postleitzahlen an.');
    }

    public function render(Request $request): Response
    {
        return response()->json(['message' => $this->getMessage()], 422);
    }
}
