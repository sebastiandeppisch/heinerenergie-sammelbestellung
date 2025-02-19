<?php

namespace App\Http\Controllers;

use App\Http\Resources\DataProtectedAdvice;
use App\Models\Advice;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
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

    public function newOrder()
    {
        return Inertia::render('NewOrder');
    }

    public function orders()
    {
        return Inertia::render('Orders');
    }

    public function profile()
    {
        return Inertia::render('Profile');
    }

    public function products()
    {
        return Inertia::render('Products');
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
            throw new AuthorizationException(
                'You are not authorized to view this advice.'
            );
        }

        return Inertia::render('Advice', [
            'advice' => $advice,
        ]);
    }

    public function advicesMap()
    {
        $advices = Advice::all()->filter(fn (Advice $advice) => Auth::user()->can('viewDataProtected', $advice))->values()->map(fn ($advice) => (new DataProtectedAdvice($advice))->resolve());

        return Inertia::render('AdvicesMap', [
            'advices' => $advices,
            'advisors' => User::all(),
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

    public function publicNewOrder()
    {
        return Inertia::render('PublicNewOrder');
    }
}
