<?php

namespace App\Http\Controllers\Api;

use App\Contracts\MailServiceContract;
use App\Http\Controllers\Controller;
use App\Http\Requests\SendMailRequest;
use App\Models\Advice;
use Illuminate\Http\JsonResponse;

class MailController extends Controller
{
    public function __construct(private readonly MailServiceContract $mailService) {}

    public function index(Advice $advice): JsonResponse
    {
        $this->authorize('view', $advice);

        return response()->json($this->mailService->getMailsForCase($advice));
    }

    public function show(Advice $advice, string $folder, string $uid): JsonResponse
    {
        $this->authorize('view', $advice);

        return response()->json($this->mailService->getMail($uid, $folder));
    }

    public function store(SendMailRequest $request, Advice $advice): JsonResponse
    {
        $this->authorize('view', $advice);

        $this->mailService->sendMail(
            $advice,
            $request->validated('subject'),
            $request->validated('body'),
        );

        return response()->json(['success' => true]);
    }
}
