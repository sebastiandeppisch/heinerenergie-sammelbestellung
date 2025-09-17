<?php

namespace App\Http\Controllers\Api;

use App\Data\DataProtectedAdviceData;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreAdviceRequest;
use App\Http\Requests\UpdateAdviceRequest;
use App\Models\Advice;
use App\Models\User;
use App\Services\AdviceService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AdviceController extends Controller
{
    public function __construct()
    {
        $this->authorizeResource(Advice::class);
    }

    public function store(StoreAdviceRequest $request)
    {
        $advice = new Advice;
        $advice->fill($request->validated());
        $advice->save();

        return $advice;
    }

    public function show(Advice $advice)
    {
        return DataProtectedAdviceData::fromModel($advice, Auth::user());
    }

    public function update(UpdateAdviceRequest $request, Advice $advice)
    {
        $advice->fill($request->validated());
        $advice->save();

        $advice = $advice->fresh();

        return DataProtectedAdviceData::fromModel($advice, Auth::user());
    }

    public function destroy(Advice $advice)
    {
        $advice->delete();

        return response()->noContent();
    }

    public function setAdvisors(Advice $advice, Request $request)
    {
        $this->auth($advice, 'addAdvisors');

        $validated = $request->validate([
            'advisor' => 'array',
            'advisor.*' => 'exists:users,id',
        ]);

        app(AdviceService::class)->syncShares($advice, $validated->advisors, $request->user());
    }

    private function auth(Advice $advice, string $ability)
    {
        if (! Auth::user()->can($ability, $advice)) {
            abort(403, 'Du hast keine Berechtigung, diese Beratung zu sehen');
        }
    }

    public function assign(Advice $advice)
    {
        if ($advice->advisor_id === null) {
            $advice->advisor_id = Auth::user()->id;
            $advice->save();
        } else {
            abort(403, 'Diese Beratung wurde bereits einem Berater zugewiesen');
        }

        return $advice;
    }

    public function sortedAdvisors(Advice $advice, AdviceService $adviceService)
    {
        return User::all()->map(function (User $user) use ($advice, $adviceService) {
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
