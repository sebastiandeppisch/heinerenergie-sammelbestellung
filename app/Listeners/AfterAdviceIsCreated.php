<?php

namespace App\Listeners;

use App\Events\AdviceCreated;
use App\Jobs\SendNewAdviceInfoToAdvisors;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class AfterAdviceIsCreated implements ShouldQueue
{
    use InteractsWithQueue;

    public function __construct() {}

    public function handle(AdviceCreated $event)
    {
        SendNewAdviceInfoToAdvisors::dispatch($event->advice);
    }
}
