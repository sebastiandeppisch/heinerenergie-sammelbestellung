<?php

namespace App\Exceptions;

use Exception;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EncryptionKeyMissing extends Exception
{
    public function render(Request $request): Response
    {
        return response()->json(['message' => 'Encryption key missing. Please log in again.'], 403);
    }
}
