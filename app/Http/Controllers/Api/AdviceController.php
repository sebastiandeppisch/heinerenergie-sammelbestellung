<?php

namespace App\Http\Controllers\Api;

use App\Events\Advice\AdviceSharedAdvisorAdded;
use App\Events\Advice\AdviceSharedAdvisorRemoved;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreAdviceRequest;
use App\Http\Requests\UpdateAdviceRequest;
use App\Mail\SendOrderLink;
use App\Models\Advice;
use App\Models\User;
use App\Services\AdviceService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;

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
        return $advice;
    }

    public function update(UpdateAdviceRequest $request, Advice $advice)
    {
        $advice->fill($request->validated());
        $advice->save();

        return $advice;
    }

    public function destroy(Advice $advice)
    {
        $advice->delete();

        return response()->noContent();
    }

    public function setAdvisors(Advice $advice, Request $request)
    {
        $this->auth($advice, 'addAdvisors');

        // Get current advisors before sync
        $currentAdvisors = $advice->shares()->pluck('advisor_id')->toArray();

        // Sync new advisors
        $advice->shares()->sync($request->advisors);

        // Get new advisors after sync
        $newAdvisors = $advice->shares()->pluck('advisor_id')->toArray();

        // Find added advisors
        $addedAdvisors = array_diff($newAdvisors, $currentAdvisors);
        foreach ($addedAdvisors as $advisorId) {
            $advisor = User::find($advisorId);
            event(new AdviceSharedAdvisorAdded(
                $advice,
                Auth::user(),
                $advisor
            ));
        }

        // Find removed advisors
        $removedAdvisors = array_diff($currentAdvisors, $newAdvisors);
        foreach ($removedAdvisors as $advisorId) {
            $advisor = User::find($advisorId);
            event(new AdviceSharedAdvisorRemoved(
                $advice,
                Auth::user(),
                $advisor
            ));
        }
    }

    private function auth(Advice $advice, string $ability)
    {
        if (! Auth::user()->can($ability, $advice)) {
            abort(403, 'Du hast keine Berechtigung, diese Beratung zu sehen');
        }
    }

    public function sendOrderLink(Advice $advice)
    {
        Mail::to($advice->email)->send(new SendOrderLink($advice));

        return response()->noContent(202);
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
            if ($distance === null) {
                // max float value
                $distance = 1e6;
            }

            return [
                'id' => $user->id,
                'name' => $name,
                'distance' => $distance,
            ];
        })->sortBy('distance')->values();
    }
}
