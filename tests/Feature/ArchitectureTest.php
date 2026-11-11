<?php

use App\Exceptions\Handler;
use App\HexNet\HexNetServiceProvider;
use App\LaravelExtensions\StrictGates\AuthServiceProvider;
use App\LaravelExtensions\StrictGates\MissingGateException;
use App\Mail\BaseNotificationMail;
use App\Providers\InstallServiceProvider;
use Illuminate\Console\Command;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Mail\Mailable;
use Illuminate\Notifications\Notification;
use Illuminate\Support\ServiceProvider;

arch()->preset()->php();

// copied from https://github.com/pestphp/pest/blob/3.x/src/ArchPresets/Laravel.php
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
    ->ignoring('App\Enums')
    ->ignoring('App\HexNet\Enums');

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
    ->ignoring(Handler::class);

arch()->expect('App')
    ->not->toImplement(Throwable::class)
    ->ignoring('App\Exceptions')
    ->ignoring(MissingGateException::class);

arch()->expect('App\Http\Middleware')
    ->classes()
    ->toHaveMethod('handle');

arch()->expect('App\Models')
    ->classes()
    ->toExtend(Model::class)
    ->ignoring('App\Models\Scopes');

arch()->expect('App\Models')
    ->classes()
    ->not->toHaveSuffix('Model');

arch()->expect('App')
    ->not->toExtend(Model::class)
    ->ignoring('App\Models')
    ->ignoring('App\HexNet\Models');

arch()->expect('App\Http\Requests')
    ->classes()
    ->toHaveSuffix('Request');

// Traits shared by requests belong to the HTTP layer too, which no other layer may use.
arch()->expect('App\Http\Requests')
    ->toExtend(FormRequest::class)
    ->ignoring('App\Http\Requests\Concerns');

arch()->expect('App\Http\Requests')
    ->toHaveMethod('rules')
    ->ignoring('App\Http\Requests\Concerns');

arch()->expect('App')
    ->not->toExtend(FormRequest::class)
    ->ignoring('App\Http\Requests');

arch()->expect('App\Console\Commands')
    ->classes()
    ->toHaveSuffix('Command');

arch()->expect('App\Console\Commands')
    ->classes()
    ->toExtend(Command::class);

arch()->expect('App\Console\Commands')
    ->classes()
    ->toHaveMethod('handle');

arch()->expect('App')
    ->not->toExtend(Command::class)
    ->ignoring('App\Console\Commands')
    ->ignoring('App\HexNet\Console');

arch()->expect('App\Mail')
    ->classes()
    ->toExtend(Mailable::class)->ignoring(BaseNotificationMail::class);

arch()->expect('App\Mail')
    ->classes()
    ->toImplement(ShouldQueue::class)->ignoring(BaseNotificationMail::class);

// The HexaNetz plugin keeps its mails in its own namespace, so removing the plugin removes them too.
arch()->expect('App\HexNet\Mail')
    ->classes()
    ->toExtend(Mailable::class)
    ->toImplement(ShouldQueue::class);

arch()->expect('App')
    ->not->toExtend(Mailable::class)
    ->ignoring(['App\Mail', 'App\HexNet\Mail']);

arch()->expect('App\Jobs')
    ->classes()
    ->toImplement(ShouldQueue::class);

arch()->expect('App\Jobs')
    ->classes()
    ->toHaveMethod('handle');

arch()->expect('App\Listeners')
    ->toHaveMethod('handle');

arch()->expect('App\Notifications')
    ->toExtend(Notification::class);

arch()->expect('App')
    ->not->toExtend(Notification::class)
    ->ignoring('App\Notifications');

arch()->expect('App\Providers')
    ->toHaveSuffix('ServiceProvider');

arch()->expect('App\Providers')
    ->toExtend(ServiceProvider::class);

arch()->expect('App\Providers')
    ->not->toBeUsed()
    ->ignoring(AuthServiceProvider::class);

arch()->expect('App')
    ->not->toExtend(ServiceProvider::class)
    ->ignoring('App\Providers')
    ->ignoring(AuthServiceProvider::class)
    ->ignoring(HexNetServiceProvider::class);

arch()->expect('App')
    ->not->toHaveSuffix('ServiceProvider')
    ->ignoring('App\Providers')
    ->ignoring(AuthServiceProvider::class)
    ->ignoring(HexNetServiceProvider::class);

arch()->expect('App')
    ->not->toHaveSuffix('Controller')
    ->ignoring('App\Http\Controllers')
    ->ignoring('App\HexNet\Http\Controllers');

arch()->expect('App\Http\Controllers')
    ->classes()
    ->toHaveSuffix('Controller');

arch()->expect('App\Http')
    ->toOnlyBeUsedIn(['App\Http', 'App\HexNet\Http']);

arch()->expect([
    'dd',
    'ddd',
    'dump',
    'env',
    'exit',
    'ray',
])->not->toBeUsed()
    ->ignoring(InstallServiceProvider::class);

arch()->expect('App\Policies')
    ->classes()
    ->toHaveSuffix('Policy');

// HexaNetz is a plugin with its own namespace. It follows the same rules inside App\HexNet.

arch()->expect('App\HexNet\Enums')
    ->toBeEnums();

arch()->expect('App\HexNet\Models')
    ->classes()
    ->toExtend(Model::class);

arch()->expect('App\HexNet\Console')
    ->classes()
    ->toHaveSuffix('Command')
    ->toExtend(Command::class);

arch()->expect('App\HexNet\Http\Controllers')
    ->classes()
    ->toHaveSuffix('Controller');

arch()->expect('App\HexNet\Policies')
    ->classes()
    ->toHaveSuffix('Policy');

arch()->expect('App\HexNet\Listeners')
    ->toHaveMethod('handle');
