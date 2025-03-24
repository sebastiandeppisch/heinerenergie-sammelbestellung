<?php

namespace Tests\Unit\Http\Middleware;

use App\Context\GroupContextContract;
use App\Http\Context\SessionGroupContextFactory;
use App\Http\Middleware\GroupContextMiddleware;
use App\Models\Group;
use App\Models\User;
use App\Services\SessionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->user = User::factory()->create(['is_admin' => false]);
    $this->group = Group::create([
        'name' => 'Test Group',
        'description' => 'Test Group Description',
    ]);

    $this->sessionService = new SessionService;
    $this->factory = new SessionGroupContextFactory($this->sessionService);
    $this->middleware = new GroupContextMiddleware(
        $this->factory,
        $this->sessionService,
        $this->user
    );
});

test('middleware binds context to container', function () {
    $this->actingAs($this->user);

    // Set up session state
    $this->sessionService->actAsGroup($this->group, true);

    // Run middleware
    $this->middleware->handle(Request::create('/'), function ($request) {
        $context = app()->make(GroupContextContract::class);

        expect($context)->toBeInstanceOf(GroupContextContract::class);
        expect($context->getCurrentGroup())->not->toBeNull();
        expect($context->getCurrentGroup()->id)->toEqual($this->group->id);
        expect($context->isActingAsDirectAdmin($this->user, $this->group))->toEqual(true);

        return 'next';
    });
});

test('middleware creates new context for each request', function () {
    $this->actingAs($this->user);

    // First request with group admin
    $this->sessionService->actAsGroup($this->group, true);

    $this->middleware->handle(Request::create('/'), function ($request) {
        $context1 = app()->make(GroupContextContract::class);
        expect($context1->isActingAsDirectAdmin($this->user, $this->group))->toEqual(true);

        return 'next';
    });

    // Second request without group admin
    app()->forgetInstance(GroupContextContract::class);
    $this->sessionService->actAsGroup($this->group, false);

    $this->middleware->handle(Request::create('/'), function ($request) {
        $context2 = app()->make(GroupContextContract::class);
        expect($context2->isActingAsDirectAdmin($this->user, $this->group))->toEqual(false);

        return 'next';
    });
});

test('middleware works with unauthenticated users', function () {
    Auth::logout();

    $this->middleware->handle(Request::create('/'), function ($request) {
        $context = app()->make(GroupContextContract::class);

        expect($context)->toBeInstanceOf(GroupContextContract::class);
        expect($context->getCurrentGroup())->toBeNull();
        // For unauthenticated users, we can't check actsAsGroupAdmin since it requires a user

        return 'next';
    });
});
