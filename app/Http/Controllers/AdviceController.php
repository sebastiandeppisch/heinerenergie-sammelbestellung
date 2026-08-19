<?php

namespace App\Http\Controllers;

use App\Data\AdviceEventData;
use App\Data\AdviceStatusNamesData;
use App\Data\ChecklistEntryData;
use App\Data\DataProtectedAdviceData;
use App\Data\FormDefinitionData;
use App\Data\FormSubmissionData;
use App\Data\GroupData;
use App\Data\GroupMapData;
use App\Data\UserData;
use App\Enums\AdviceType;
use App\Enums\FormType;
use App\Events\Advice\CommentAddedEvent;
use App\Events\Advice\InitiativeTransferEvent;
use App\Http\Controllers\Concerns\HandlesChecklistEntries;
use App\Http\Requests\StoreAdviceCommentRequest;
use App\Http\Requests\StoreAdviceRequest;
use App\Http\Requests\TransferAdviceRequest;
use App\Http\Requests\UpdateAdviceRequest;
use App\Models\Advice;
use App\Models\AdviceStatus;
use App\Models\FormDefinition;
use App\Models\FormSubmission;
use App\Models\Group;
use App\Models\User;
use App\Notifications\AdviceTransferred;
use App\Services\AdviceService;
use App\Services\CurrentGroupService;
use App\Services\SessionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Wnx\Sends\Models\Send;

class AdviceController extends Controller
{
    use HandlesChecklistEntries;

    public function index(SessionService $sessionService)
    {
        $currentGroup = $sessionService->getCurrentGroup();
        $showGroupColumn = $sessionService->actsAsSystemAdmin()
            || ($sessionService->actsAsGroupAdmin() && $currentGroup !== null && ! $currentGroup->isLeaf());

        $user = Auth::user();

        $advices = app(AdviceService::class)->getAdvicesListForUser($user);

        $groups = Group::with('parent')->get()
        // ->filter(fn (Group $group) => Auth::user()->can('view', $group))
            ->map(fn (Group $group) => GroupData::fromModel($group))->values()->toArray();

        $adviceStatuses = AdviceStatus::all()->map(fn (AdviceStatus $status) => ['id' => $status->uuid, 'name' => $status->name]);
        $advisors = User::all()
            ->map(fn (User $u) => ['id' => $u->uuid, 'name' => "{$u->first_name} {$u->last_name}"])
            ->toArray();

        return Inertia::render('Advices', [
            'showGroupColumn' => $showGroupColumn,
            'advices' => $advices,
            'groups' => $groups,
            'adviceStatuses' => $adviceStatuses,
            'adviceTypes' => [
                ['id' => 0, 'name' => 'Zuhause'],
                ['id' => 1, 'name' => 'Virtuell'],
                ['id' => 2, 'name' => 'Sammelbestellung'],
            ],
            'advisors' => $advisors,
        ]);
    }

    public function store(StoreAdviceRequest $request, SessionService $sessionService)
    {
        $this->authorize('create', Advice::class);

        $currentGroup = $sessionService->getCurrentGroup();

        $advice = new Advice;
        $advice->fill($request->validated());
        $advice->advisor_id = Auth::id();
        if ($currentGroup !== null) {
            $advice->group_id = $currentGroup->id;
        } else {
            $groupUuid = $request->validated()['group_id'];
            $advice->group_id = Group::where('uuid', $groupUuid)->firstOrFail()->id;
        }
        $advice->save();

        return redirect()->route('advices.show', $advice)
            ->with('success', 'Beratung erfolgreich angelegt');
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

        $adviceType = $advice->type;
        $canDeleteAdvice = Auth::user()->can('delete', $advice);

        $checklistEntries = $advice->checklistEntries()->with('formDefinition.fields.options')->get()
            ->map(fn ($entry) => ChecklistEntryData::fromModel($entry));

        $availableChecklists = FormDefinition::where('group_id', $advice->group_id)
            ->where('type', FormType::Checklist)
            ->whereNotIn('id', $advice->checklistEntries()->pluck('form_definition_id'))
            ->with('fields.options', 'group')
            ->get()
            ->map(fn ($fd) => FormDefinitionData::fromModel($fd));

        $advice = DataProtectedAdviceData::fromModel($advice, Auth::user());

        // Get advice status options (filtered by user permissions)
        $adviceStatusOptions = AdviceStatus::all()
            ->filter(fn (AdviceStatus $status) => Auth::user()->can('view', $status))
            ->map(fn (AdviceStatus $status) => AdviceStatusNamesData::fromModel($status))
            ->values()
            ->toArray();

        // Get advice type options from enum
        $adviceTypesOptions = AdviceType::getSelectableOptions($adviceType);

        return Inertia::render('Advice', [
            'advice' => $advice,
            'events' => $timeline,
            'transferableGroups' => $transferableGroups,
            'formSubmission' => $formSubmission,
            'adviceStatusOptions' => $adviceStatusOptions,
            'adviceTypesOptions' => $adviceTypesOptions,
            'canDeleteAdvice' => $canDeleteAdvice,
            'checklistEntries' => $checklistEntries,
            'availableChecklists' => $availableChecklists,
        ]);
    }

    public function update(Advice $advice, UpdateAdviceRequest $request)
    {
        $advice->update($request->validated());

        return redirect()->back()->with('success', 'Beratung gespeichert');
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

    public function map(CurrentGroupService $currentGroupService)
    {
        $user = Auth::user();
        $advices = app(AdviceService::class)->getAdvicesListForUser($user);

        $groups = Group::where('accepts_transfers', true)->get()->filter(fn (Group $group) => $group->consulting_area !== null)->map(fn (Group $group) => GroupMapData::fromModel($group))->values();

        $advisors = User::where('is_active', true)->get()->filter(fn (User $advisor) => $user->can('view', $advisor))->map(fn ($user) => UserData::fromModel($user, false))->values();

        // Get marker from current group, or use default
        $currentGroup = $currentGroupService->getGroup();
        $advisorMarker = '/images/markers/he_yellow.svg'; // Default marker

        if ($currentGroup && $currentGroup->full_marker_path) {
            $advisorMarker = url($currentGroup->full_marker_path);
        }

        return Inertia::render('AdvicesMap', [
            'advices' => $advices,
            'advisors' => $advisors, // TODO filter
            'groups' => $groups,
            'advisorMarker' => $advisorMarker,
        ]);
    }

    public function delete(Advice $advice)
    {
        $this->authorize('delete', $advice);

        $advice->delete();

        return redirect()->route('advices')->with('success', 'Die Beratung wurde erfolgreich gelöscht.');
    }

    public function updateStatus(Advice $advice, Request $request): RedirectResponse
    {
        $this->authorize('update', $advice);

        $validated = $request->validate([
            'advice_status_id' => 'required|uuid|exists:advice_status,uuid',
        ]);

        $status = AdviceStatus::where('uuid', $validated['advice_status_id'])->firstOrFail();

        $advice->advice_status_id = $status->id;
        $advice->save();

        return back();
    }

    public function updateAdvisor(Advice $advice, Request $request): RedirectResponse
    {
        $this->authorize('update', $advice);

        $validated = $request->validate([
            'advisor_id' => 'required|integer|nullable|exists:users,uuid',
        ]);

        $advisor = User::where('uuid', $validated['advisor_id'])->firstOrFail();

        $advice->advisor_id = $advisor->id;
        $advice->save();

        return back()->with('success', 'Der/Die Berater/in wurde aktualisiert');
    }

    public function assign(Advice $advice): RedirectResponse
    {
        $this->authorize('viewDataProtected', $advice);

        if ($advice->advisor_id === null) {
            $advice->advisor_id = Auth::user()->id;
            $advice->save();
        } else {
            abort(403, 'Diese Beratung wurde bereits einem Berater zugewiesen');
        }

        return back()->with('success', 'Die Beratung wurde Dir zugewiesen');
    }
}
