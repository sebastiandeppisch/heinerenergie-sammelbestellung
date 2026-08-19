<?php

declare(strict_types=1);

namespace App\Exceptions;

use Exception;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class MailCredentialsMissing extends Exception
{
    public function render(Request $request): Response
    {
        return response()->json(['message' => 'No mail account configured. Please set up your mail account first.'], 403);
    }
}
