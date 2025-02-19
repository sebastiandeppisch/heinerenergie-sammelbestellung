<?php

namespace App\Providers;

use App\Events\AdviceCreated;
use App\Events\AdviceUpdated;
use App\Events\OrderCreated;
use App\Listeners\AfterAdviceIsCreated;
use App\Listeners\CalculateCoordinates;
use App\Listeners\EmptyCoordinates;
use App\Listeners\SendOrderCreatedNotification;
use Illuminate\Auth\Events\Registered;
use Illuminate\Auth\Listeners\SendEmailVerificationNotification;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Illuminate\Mail\Events\MessageSent;
use Illuminate\Support\Facades\Event;
use Wnx\Sends\Listeners\StoreOutgoingMailListener;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event listener mappings for the application.
     *
     * @var array<class-string, array<int, class-string>>
     */
    protected $listen = [
        Registered::class => [
            SendEmailVerificationNotification::class,
        ],
        OrderCreated::class => [
            SendOrderCreatedNotification::class,
        ],
        AdviceCreated::class => [
            CalculateCoordinates::class,
            AfterAdviceIsCreated::class,
        ],
        AdviceUpdated::class => [
            EmptyCoordinates::class,
            CalculateCoordinates::class,
        ],
        MessageSent::class => [
            StoreOutgoingMailListener::class,
        ],
    ];

    /**
     * Register any events for your application.
     *
     * @return void
     */
    #[\Override]
    public function boot()
    {
        //
    }
}
