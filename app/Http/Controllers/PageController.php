<?php

namespace App\Http\Controllers;

use App\Data\AdviceEventData;
use App\Data\GroupData;
use App\Data\GroupMapData;
use App\Http\Resources\DataProtectedAdvice;
use App\Models\Advice;
use App\Models\Group;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class PageController extends Controller
{
    // TODO move all the pages to their own controllers

    public function login()
    {
        if (auth()->check()) {
            return redirect()->route('dashboard');
        }

        return Inertia::render('LoginForm');
    }

    public function dashboard()
    {
        return Inertia::render('Dashboard');
    }

    public function profile()
    {
        return Inertia::render('Profile');
    }

    public function users()
    {
        return Inertia::render('Users');
    }

    public function settings()
    {
        return Inertia::render('Settings');
    }

    public function advices()
    {
        return Inertia::render('AdvicesTable');
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

    public function advicesMap()
    {
        $advices = Advice::all()->filter(fn (Advice $advice) => Auth::user()->can('viewDataProtected', $advice))->values()->map(fn ($advice) => (new DataProtectedAdvice($advice))->resolve());

        $groups = Group::where('accepts_transfers', true)->get()->map(fn (Group $group) => GroupMapData::fromModel($group))->filter(fn (GroupMapData $group) => $group->polygon !== null)->values();

        return Inertia::render('AdvicesMap', [
            'advices' => $advices,
            'advisors' => User::all(),
            'groups' => $groups,
        ]);
    }

    public function resetPassword()
    {
        /*if(auth()->check()){
            return redirect()->route('dashboard');
        }*/

        return Inertia::render('ResetPasswordForm');
    }

    public function changePassword(Request $request)
    {
        return Inertia::render('ChangePasswordForm', [
            'token' => $request->get('token'),
            'email' => $request->get('email'),
        ]);
    }

    public function newAdvice()
    {
        return Inertia::render('NewAdvice');
    }

    public function impress()
    {
        return Inertia::render('HtmlContent', [
            'content' => Setting::get('impress'),
        ]);
    }

    public function dataPolicy()
    {
        return Inertia::render('HtmlContent', [
            'content' => Setting::get('datapolicy'),
        ]);
    }

    public function initiativeSelection(#[CurrentUser] User $user)
    {
        return Inertia::render('InitiativeSelection', [
            'initiatives' => $user->groups->map(fn ($group) => GroupData::fromModel($group)),
            'canActAsSystemAdmin' => $user->is_admin,
        ]);
    }
}
