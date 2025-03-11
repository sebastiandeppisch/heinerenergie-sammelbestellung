<?php

namespace App\Http\Controllers;

use App\Data\AdviceEventData;
use App\Data\DataProtectedAdviceData;
use App\Data\GroupData;
use App\Data\GroupMapData;
use App\Events\Advice\InitiativeTransferEvent;
use App\Http\Requests\TransferAdviceRequest;
use App\Models\Advice;
use App\Models\Group;
use App\Models\User;
use App\Notifications\AdviceTransferred;
use App\Services\SessionService;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class AdviceController extends Controller
{
    public function index(SessionService $sessionService)
    {
        $onlyOneGroup = $sessionService->getCurrentGroup() !== null && ! $sessionService->actsAsSystemAdmin() && ! $sessionService->actsAsGroupAdmin();

        $advices = Advice::all()->filter(fn (Advice $advice) => Auth::user()->can('viewDataProtected', $advice))->values()->map(fn ($advice) => DataProtectedAdviceData::fromModel($advice))->toArray();

        $groups = Group::all()
        // ->filter(fn (Group $group) => Auth::user()->can('view', $group))
            ->map(fn (Group $group) => GroupData::fromModel($group))->values()->toArray();

        return Inertia::render('AdvicesTable', [
            'onlyOneGroup' => $onlyOneGroup,
            'advices' => $advices,
            'groups' => $groups,
        ]);
    }

    public function showAdvice(Advice $advice)
    {
        if (! Auth::user()->can('view', $advice)) {
            return redirect('/advices')->withErrors('Du hast keine Berechtigung, diese Beratung zu sehen');
        }

        $events = $advice->events()
            ->get()
            ->map(fn ($event) => AdviceEventData::fromModel($event));

        $mails = $advice->sends()
            ->get()
            ->map(fn ($mail) => AdviceEventData::fromMail($mail));

        $timeline = $events->concat($mails)
            ->sortBy(fn ($item) => $item->created_at)
            ->values();

        $coordinateOfAdvice = $advice->coordinate;

        $transferableGroups = Group::where('accepts_transfers', true)->get()
            ->sortBy(function (Group $group) use ($coordinateOfAdvice) {
                $center = $group->consulting_area?->getCenter();

                if ($center === null || $coordinateOfAdvice === null) {
                    return INF;
                }

                return $coordinateOfAdvice->distanceTo($center);
            })
            ->map(fn (Group $group) => GroupData::fromModel($group))
            ->values();

        return Inertia::render('Advice', [
            'advice' => $advice,
            'events' => $timeline,
            'transferableGroups' => $transferableGroups,
        ]);
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

    public function unassign(Advice $advice)
    {
        $this->authorize('update', $advice);

        $advice->advisor_id = null;
        $advice->save();

        return redirect()->route('advices.show', $advice);
    }

    public function map()
    {
        $advices = Advice::all()->filter(fn (Advice $advice) => Auth::user()->can('viewDataProtected', $advice))->values()->map(fn ($advice) => DataProtectedAdviceData::fromModel($advice));

        $groups = Group::where('accepts_transfers', true)->get()->map(fn (Group $group) => GroupMapData::fromModel($group))->filter(fn (GroupMapData $group) => $group->polygon !== null)->values();

        return Inertia::render('AdvicesMap', [
            'advices' => $advices,
            'advisors' => User::all(),
            'groups' => $groups,
        ]);
    }
}
