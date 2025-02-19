<?php


arch()->preset()->php();

//copied from https://github.com/pestphp/pest/blob/3.x/src/ArchPresets/Laravel.php
// but removed Controller method naming convention

arch('No debugging calls are used')
    ->expect(['dd', 'dump'])
    ->not->toBeUsed();

arch()->expect('App\Traits')
    ->toBeTraits();

arch()->expect('App\Concerns')
    ->toBeTraits();

arch()->expect('App')
    ->not->toBeEnums()
    ->ignoring('App\Enums');

arch()->expect('App\Enums')
    ->toBeEnums()
    ->ignoring('App\Enums\Concerns');

arch()->expect('App\Features')
    ->toBeClasses()
    ->ignoring('App\Features\Concerns');

arch()->expect('App\Features')
    ->toHaveMethod('resolve');

arch()->expect('App\Exceptions')
    ->classes()
    ->toImplement('Throwable')
    ->ignoring(\App\Exceptions\Handler::class);

arch()->expect('App')
    ->not->toImplement(Throwable::class)
    ->ignoring('App\Exceptions');

arch()->expect('App\Http\Middleware')
    ->classes()
    ->toHaveMethod('handle');

arch()->expect('App\Models')
    ->classes()
    ->toExtend(\Illuminate\Database\Eloquent\Model::class)
    ->ignoring('App\Models\Scopes');

arch()->expect('App\Models')
    ->classes()
    ->not->toHaveSuffix('Model');

arch()->expect('App')
    ->not->toExtend(\Illuminate\Database\Eloquent\Model::class)
    ->ignoring('App\Models');

arch()->expect('App\Http\Requests')
    ->classes()
    ->toHaveSuffix('Request');

arch()->expect('App\Http\Requests')
    ->toExtend(\Illuminate\Foundation\Http\FormRequest::class);

arch()->expect('App\Http\Requests')
    ->toHaveMethod('rules');

arch()->expect('App')
    ->not->toExtend(\Illuminate\Foundation\Http\FormRequest::class)
    ->ignoring('App\Http\Requests');

arch()->expect('App\Console\Commands')
    ->classes()
    ->toHaveSuffix('Command');

arch()->expect('App\Console\Commands')
    ->classes()
    ->toExtend(\Illuminate\Console\Command::class);

arch()->expect('App\Console\Commands')
    ->classes()
    ->toHaveMethod('handle');

arch()->expect('App')
    ->not->toExtend(\Illuminate\Console\Command::class)
    ->ignoring('App\Console\Commands');

arch()->expect('App\Mail')
    ->classes()
    ->toExtend(\Illuminate\Mail\Mailable::class);

arch()->expect('App\Mail')
    ->classes()
    ->toImplement(\Illuminate\Contracts\Queue\ShouldQueue::class);

arch()->expect('App')
    ->not->toExtend(\Illuminate\Mail\Mailable::class)
    ->ignoring('App\Mail');

arch()->expect('App\Jobs')
    ->classes()
    ->toImplement(\Illuminate\Contracts\Queue\ShouldQueue::class);

arch()->expect('App\Jobs')
    ->classes()
    ->toHaveMethod('handle');

arch()->expect('App\Listeners')
    ->toHaveMethod('handle');

arch()->expect('App\Notifications')
    ->toExtend(\Illuminate\Notifications\Notification::class);

arch()->expect('App')
    ->not->toExtend(\Illuminate\Notifications\Notification::class)
    ->ignoring('App\Notifications');

arch()->expect('App\Providers')
    ->toHaveSuffix('ServiceProvider');

arch()->expect('App\Providers')
    ->toExtend(\Illuminate\Support\ServiceProvider::class);

arch()->expect('App\Providers')
    ->not->toBeUsed();

arch()->expect('App')
    ->not->toExtend(\Illuminate\Support\ServiceProvider::class)
    ->ignoring('App\Providers');

arch()->expect('App')
    ->not->toHaveSuffix('ServiceProvider')
    ->ignoring('App\Providers');

arch()->expect('App')
    ->not->toHaveSuffix('Controller')
    ->ignoring('App\Http\Controllers');

arch()->expect('App\Http\Controllers')
    ->classes()
    ->toHaveSuffix('Controller');

arch()->expect('App\Http')
    ->toOnlyBeUsedIn('App\Http');


arch()->expect([
    'dd',
    'ddd',
    'dump',
    'env',
    'exit',
    'ray',
])->not->toBeUsed();

arch()->expect('App\Policies')
    ->classes()
    ->toHaveSuffix('Policy');