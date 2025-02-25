<?php

namespace App\Http\Controllers;

use App\Events\Advice\AdviceSharedAdvisorAdded;
use App\Events\Advice\AdviceSharedAdvisorRemoved;
use App\Events\Advice\InitiativeTransferEvent;
use App\Http\Requests\StoreAdviceRequest;
use App\Http\Requests\TransferAdviceRequest;
use App\Http\Requests\UpdateAdviceRequest;
use App\Http\Resources\DataProtectedAdvice;
use App\Mail\SendOrderLink;
use App\Models\Advice;
use App\Models\Group;
use App\Models\User;
use App\Notifications\AdviceTransferred;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;

class AdviceController extends Controller
{
    public function __construct()
    {
        $this->authorizeResource(Advice::class);
    }

    public function index()
    {
        $isAdmin = Auth::user()->isActingAsAdmin();

        return Advice::all()->filter(fn (Advice $advice) => Auth::user()->can('viewDataProtected', $advice))->values()->map(fn ($advice) => new DataProtectedAdvice($advice));
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

    public function unassign(Advice $advice)
    {
        $this->authorize('update', $advice);

        $advice->advisor_id = null;
        $advice->save();

        return redirect()->route('advices.show', $advice);
    }

    public function sortedAdvisors(Advice $advice)
    {
        return User::all()->map(function (User $user) use ($advice) {
            $name = $user->name;
            $distance = $advice->getDistanceToUser($user);
            if ($distance !== null) {
                $name = $name.' ('.$this->formatValue($distance, 'm').')';
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

    private function formatValue(float $n, string $unit, int $significant = 3): string
    {

        $ranges = [
            ['divider' => 1e18, 'suffix' => 'P'],
            ['divider' => 1e15, 'suffix' => 'E'],
            ['divider' => 1e12, 'suffix' => 'T'],
            ['divider' => 1e9, 'suffix' => 'G'],
            ['divider' => 1e6, 'suffix' => 'M'],
            ['divider' => 1e3, 'suffix' => 'k'],
        ];
        foreach ($ranges as $range) {
            if ($n >= $range['divider']) {
                $number = $n / $range['divider'];
                $number = $this->roundSigDigs($number, $significant);

                return ((string) $number).' '.$range['suffix'].$unit;
            }
        }
        $number = $this->roundSigDigs($n, $significant);

        return ((string) $number).' '.$unit;
    }

    private function roundSigDigs(float $number, int $sigdigs): float
    {
        $multiplier = 1;
        while ($number < 0.1) {
            $number *= 10;
            $multiplier /= 10;
        }
        while ($number >= 1) {
            $number /= 10;
            $multiplier *= 10;
        }

        return round($number, $sigdigs) * $multiplier;
    }

    public function transfer(Advice $advice, TransferAdviceRequest $request)
    {
        $this->authorize('update', $advice);

        $targetGroup = Group::findOrFail($request->group_id);

        if (! $targetGroup->accepts_transfers) {
            abort(403, 'Diese Initiative akzeptiert keine Beratungsübertragungen');
        }

        $oldGroup = $advice->group;
        $advice->group()->associate($targetGroup);
        $advice->save();

        event(new InitiativeTransferEvent(
            $advice,
            Auth::user(),
            $oldGroup,
            $targetGroup,
            $request->reason
        ));

        $advice->notify(new AdviceTransferred($advice, $oldGroup, $targetGroup, $request->reason));

        return redirect()->route('advices.show', $advice)
            ->with('success', 'Beratung wurde erfolgreich übertragen. Eine Benachrichtigung wurde versendet.');
    }
}
