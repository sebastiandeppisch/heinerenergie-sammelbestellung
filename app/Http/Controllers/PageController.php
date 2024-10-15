<?php

namespace App\Http\Controllers;

use Inertia\Inertia;
use Illuminate\Http\Request;

class PageController extends Controller
{
    public function login(){
        if(auth()->check()){
            return redirect()->route('dashboard');
        }

        return Inertia::render('LoginForm');
    }

    public function dashboard(){
        return Inertia::render('Dashboard');
    }

    public function newOrder(){
        return Inertia::render('NewOrder');
    }

    public function orders(){
        return Inertia::render('Orders');
    }

    public function profile(){
        return Inertia::render('Profile');
    }
}
