<?php

namespace App\Providers;

use App\Events\OrderCreated;
use App\Events\AdviceCreated;
use App\Events\AdviceUpdated;
use Illuminate\Support\Facades\Event;
use Illuminate\Auth\Events\Registered;
use App\Listeners\CalculateCoordinates;
use App\Listeners\SendOrderCreatedNotification;
use App\Listeners\EmptyCoordinates;
use Illuminate\Auth\Listeners\SendEmailVerificationNotification;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

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
            SendOrderCreatedNotification::class
        ],
        AdviceCreated::class =>[
            CalculateCoordinates::class
        ],
        AdviceUpdated::class =>[
            EmptyCoordinates::class,
            CalculateCoordinates::class
        ]
    ];

    /**
     * Register any events for your application.
     *
     * @return void
     */
    public function boot()
    {
        //
    }
}
