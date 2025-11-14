<?php

namespace App\Http\Controllers;

use App\Data\AdviceEventData;
use App\Data\DataProtectedAdviceData;
use App\Data\FormSubmissionData;
use App\Data\GroupData;
use App\Data\GroupMapData;
use App\Data\UserData;
use App\Events\Advice\CommentAddedEvent;
use App\Events\Advice\InitiativeTransferEvent;
use App\Http\Requests\StoreAdviceCommentRequest;
use App\Http\Requests\TransferAdviceRequest;
use App\Models\Advice;
use App\Models\FormSubmission;
use App\Models\Group;
use App\Models\User;
use App\Notifications\AdviceTransferred;
use App\Services\AdviceService;
use App\Services\SessionService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Wnx\Sends\Models\Send;

class AdviceController extends Controller
{
    public function index(SessionService $sessionService)
    {
        $onlyOneGroup = $sessionService->getCurrentGroup() !== null && $sessionService->getCurrentGroup()->isLeaf();

        $user = Auth::user();

        $advices = app(AdviceService::class)->getAdvicesListForUser($user);

        $groups = Group::with('parent')->get()
        // ->filter(fn (Group $group) => Auth::user()->can('view', $group))
            ->map(fn (Group $group) => GroupData::fromModel($group))->values()->toArray();

        return Inertia::render('AdvicesTable', [
            'onlyOneGroup' => $onlyOneGroup,
            'advices' => $advices,
            'groups' => $groups,
        ]);
    }

    public function show(Advice $advice)
    {
        $advice->loadMissing('shares', 'group', 'group.parent', 'advisor');
        if (! Auth::user()->can('view', $advice)) {
            return redirect('/advices')->with('error', 'Du hast keine Berechtigung, diese Beratung zu sehen');
        }

        $advice->loadMissing('events', 'events.user');

        $events = $advice->events()
            ->with('user')
            ->get()
            ->map(fn ($event) => AdviceEventData::fromModel($event));

        /** @var Collection<int, Send> $mails */
        $mails = $advice->sends()->get();
        $mails = $mails->map(fn ($mail) => AdviceEventData::fromMail($mail));

        $timeline = $events->concat($mails)
            ->sortBy(fn ($item) => $item->created_at)
            ->values();

        $coordinateOfAdvice = $advice->coordinate;

        $transferableGroups = Group::where('accepts_transfers', true)->with('parent')->get()
            ->sortBy(function (Group $group) use ($coordinateOfAdvice) {
                $center = $group->consulting_area?->getCenter();

                if ($center === null || $coordinateOfAdvice === null) {
                    return INF;
                }

                return $coordinateOfAdvice->distanceTo($center)->getValue();
            })
            ->map(fn (Group $group) => GroupData::fromModel($group))
            ->values();

        $formSubmission = FormSubmission::where('advice_id', $advice->id)->with('submissionFields', 'submissionFields.options')->first();
        if ($formSubmission !== null) {
            $formSubmission = FormSubmissionData::fromModel($formSubmission);

            $formSubmission->fields = $formSubmission->fields->filter(fn ($field) => ! in_array($field->field->label, ['Vorname', 'Nachname', 'Adresse', 'E-Mail Adresse', 'Telefonnummer', 'Möchtest Du virtuell oder bei Dir vor Ort beraten werden?']));
        }

        $advice = DataProtectedAdviceData::fromModel($advice, Auth::user());

        return Inertia::render('Advice', [
            'advice' => $advice,
            'events' => $timeline,
            'transferableGroups' => $transferableGroups,
            'formSubmission' => $formSubmission,
        ]);
    }

    public function transfer(Advice $advice, TransferAdviceRequest $request)
    {
        $targetGroup = Group::where('uuid', $request->group_id)->firstOrFail();
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

    public function storeComment(Advice $advice, StoreAdviceCommentRequest $request)
    {
        $this->authorize('storeComment', $advice);
        event(new CommentAddedEvent(
            comment: $request->comment,
            author: Auth::user(),
            advice: $advice
        ));

        return redirect()->back();
    }

    public function unassign(Advice $advice)
    {
        $this->authorize('unassign', $advice);

        $advice->advisor_id = null;
        $advice->save();

        return redirect()->route('advices')->with('info', 'Die Beratung wurde wieder freigegeben');
    }

    public function map()
    {
        $user = Auth::user();
        $advices = app(AdviceService::class)->getAdvicesListForUser($user);

        $groups = Group::where('accepts_transfers', true)->get()->filter(fn (Group $group) => $group->consulting_area !== null)->map(fn (Group $group) => GroupMapData::fromModel($group))->values();

        $advisors = User::all()->filter(fn (User $advisor) => $user->can('view', $advisor))->map(fn ($user) => UserData::fromModel($user, false));
        return Inertia::render('AdvicesMap', [
            'advices' => $advices,
            'advisors' => $advisors, // TODO filter
            'groups' => $groups,
        ]);
    }
}
