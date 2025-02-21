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

        expect($context)->toBeInstanceOf(GroupContextContract::class)
            ->and($context->getCurrentGroup()->id)->toBe($this->group->id)
            ->and($context->actsAsGroupAdmin())->toBeTrue();

        return 'next';
    });
})->skip('use group context in DI container to make this feature work');

test('middleware creates new context for each request', function () {
    $this->actingAs($this->user);

    // First request with group admin
    $this->sessionService->actAsGroup($this->group, true);

    $this->middleware->handle(Request::create('/'), function ($request) {
        $context1 = app()->make(GroupContextContract::class);
        expect($context1->actsAsGroupAdmin())->toBeTrue();

        return 'next';
    });

    // Second request without group admin
    $this->sessionService->actAsGroup($this->group, false);

    $this->middleware->handle(Request::create('/'), function ($request) {
        $context2 = app()->make(GroupContextContract::class);
        expect($context2->actsAsGroupAdmin())->toBeFalse();

        return 'next';
    });
})->skip('use group context in DI container to make this feature work');

test('middleware works with unauthenticated users', function () {
    $this->middleware->handle(Request::create('/'), function ($request) {
        $context = app()->make(GroupContextContract::class);

        expect($context)->toBeInstanceOf(GroupContextContract::class)
            ->and($context->getCurrentGroup())->toBeNull()
            ->and($context->actsAsGroupAdmin())->toBeFalse();

        return 'next';
    });
})->skip('use group context in DI container to make this feature work');
