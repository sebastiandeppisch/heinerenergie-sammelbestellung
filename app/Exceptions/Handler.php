<?php

declare(strict_types=1);

namespace App\Exceptions;

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Override;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Throwable;

class Handler extends ExceptionHandler
{
    /**
     * A list of the exception types that are not reported.
     *
     * @var array<int, class-string<Throwable>>
     */
    protected $dontReport = [
        //
    ];

    /**
     * A list of the inputs that are never flashed for validation exceptions.
     *
     * @var array<int, string>
     */
    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
    ];

    /**
     * Register the exception handling callbacks for the application.
     */
    #[Override]
    public function register(): void
    {
        $this->reportable(function (Throwable $e): void {
            //
        });

        $this->renderable(fn (AuthorizationException $e, Request $request): mixed => $this->renderForbidden($request, $e->getMessage()));

        $this->renderable(function (HttpExceptionInterface $e, Request $request) {
            if ($e->getStatusCode() !== 403) {
                return null;
            }

            return $this->renderForbidden($request, $e->getMessage());
        });
    }

    #[Override]
    protected function unauthenticated($request, AuthenticationException $exception)
    {
        if ($request->expectsJson()) {
            return response()->json(['error' => 'Unauthenticated.'], 401);
        }

        return redirect()->route('login');
    }

    /**
     * Present a 403 the way the user expects it: as a modal on top of the current page
     * when the request came from the SPA, and as a full error page on a direct visit.
     *
     * Returns null to fall back to the default handling (API clients, guests).
     */
    private function renderForbidden(Request $request, string $message): mixed
    {
        if ($request->is('api/*') || ($request->expectsJson() && ! $request->inertia())) {
            return null;
        }

        if (! $request->user()) {
            return null;
        }

        $intendedUrl = $request->isMethod('GET') ? $request->fullUrl() : null;
        $message = $this->forbiddenMessage($message);

        if ($request->inertia()) {
            session()->flash('authorizationError', [
                'message' => $message,
                'intendedUrl' => $intendedUrl,
            ]);

            return redirect()->to(
                $this->backUrl($request),
                $request->isMethodSafe() ? 302 : 303
            );
        }

        return Inertia::render('Error', [
            'status' => 403,
            'message' => $message,
            'intendedUrl' => $intendedUrl,
        ])->toResponse($request)->setStatusCode(403);
    }

    /**
     * The page to return to, guarding against redirecting back onto the forbidden URL itself.
     */
    private function backUrl(Request $request): string
    {
        $previous = url()->previous(route('dashboard'));

        if ($previous === $request->fullUrl() || $previous === $request->url()) {
            return route('dashboard');
        }

        return $previous;
    }

    private function forbiddenMessage(string $message): string
    {
        $frameworkDefaults = [
            '',
            'This action is unauthorized.',
            'Forbidden',
        ];

        if (in_array($message, $frameworkDefaults, true)) {
            return 'Du darfst diese Aktion nicht ausführen.';
        }

        return $message;
    }
}
