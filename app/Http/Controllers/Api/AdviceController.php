<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Data\DataProtectedAdviceData;
use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateAdviceRequest;
use App\Models\Advice;
use App\Models\User;
use App\Services\AdviceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;

class AdviceController extends Controller
{
    public function __construct()
    {
        $this->authorizeResource(Advice::class);
    }

    public function show(Advice $advice): DataProtectedAdviceData
    {
        return DataProtectedAdviceData::fromModel($advice, Auth::user());
    }

    public function update(UpdateAdviceRequest $request, Advice $advice): DataProtectedAdviceData
    {
        $advice->fill($request->validated());
        $advice->save();

        $advice = $advice->fresh();

        return DataProtectedAdviceData::fromModel($advice, Auth::user());
    }

    public function destroy(Advice $advice): Response
    {
        $advice->delete();

        return response()->noContent();
    }

    public function setAdvisors(Advice $advice, Request $request): void
    {
        $this->auth($advice, 'addAdvisors');

        $validated = $request->validate([
            'advisors' => ['array'],
            'advisors.*' => ['exists:users,uuid'],
        ]);

        /** @var array<int, string> $advisors */
        $advisors = $validated['advisors'] ?? [];
        /** @var Collection<int, User> $newAdvisors */
        $newAdvisors = User::whereIn('uuid', $advisors)->get();
        app(AdviceService::class)->syncShares($advice, $newAdvisors, $request->user());
    }

    private function auth(Advice $advice, string $ability): void
    {
        if (! Auth::user()->can($ability, $advice)) {
            abort(403, 'Du hast keine Berechtigung, diese Beratung zu sehen');
        }
    }

    public function formSubmission(Advice $advice, AdviceService $adviceService): JsonResponse
    {
        $this->auth($advice, 'viewDataProtected');

        return response()->json([
            'formSubmission' => $adviceService->getFilteredFormSubmission($advice),
        ]);
    }

    public function assign(Advice $advice): Advice
    {
        if ($advice->advisor_id === null) {
            $advice->advisor_id = Auth::user()->id;
            $advice->save();
        } else {
            abort(403, 'Diese Beratung wurde bereits einem Berater zugewiesen');
        }

        return $advice;
    }

    public function sortedAdvisors(Advice $advice, AdviceService $adviceService): JsonResponse
    {
        return User::where('is_active', true)->get()->map(function (User $user) use ($advice, $adviceService): array {
            $name = $user->name;
            $distance = $adviceService->getDistance($advice, $user);
            if ($distance !== null) {
                $name = $name.' ('.$distance.')';
            }

            $distance = $distance?->getValue();

            if ($distance === null) {
                // max float value
                $distance = 1e6;
            }

            return [
                'id' => $user->uuid,
                'name' => $name,
                'distance' => $distance,
            ];
        })->sortBy('distance')->values();
    }
}
