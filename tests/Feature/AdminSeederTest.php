<?php

use App\Models\Group;
use App\Models\User;
use Database\Seeders\AdminSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;

uses(RefreshDatabase::class);

test('the system admin is created from the seed config like a registration', function (): void {
    Group::factory()->create();
    $firstGroup = Group::query()->orderBy('id')->firstOrFail();
    config(['seed.admin' => ['first_name' => 'Ada', 'last_name' => 'Lovelace', 'email' => 'ada@example.com', 'password' => 'geheim123']]);

    $this->seed(AdminSeeder::class);

    $user = User::where('email', 'ada@example.com')->firstOrFail();
    expect($user->is_admin)->toBeTrue()
        ->and($user->first_name)->toBe('Ada')
        ->and(Hash::check('geheim123', $user->password))->toBeTrue()
        ->and($user->groups()->wherePivot('is_admin', true)->pluck('groups.id')->all())->toBe([$firstGroup->id]);
});

test('running it again updates the admin instead of creating another one', function (): void {
    config(['seed.admin' => ['first_name' => 'Ada', 'last_name' => 'Lovelace', 'email' => 'ada@example.com', 'password' => 'geheim123']]);
    $this->seed(AdminSeeder::class);

    config(['seed.admin.password' => 'anderes-passwort']);
    $this->seed(AdminSeeder::class);

    expect(User::count())->toBe(1)
        ->and(Hash::check('anderes-passwort', User::first()->password))->toBeTrue();
});

test('nothing is created without email and password', function (): void {
    config(['seed.admin' => ['first_name' => 'Ada', 'last_name' => 'Lovelace', 'email' => null, 'password' => null]]);

    $this->seed(AdminSeeder::class);

    expect(User::count())->toBe(0);
});
