<?php

namespace App\Listeners;

use App\Events\AdviceCreated;
use Illuminate\Queue\InteractsWithQueue;
use App\Jobs\SendNewAdviceInfoToAdvisors;
use Illuminate\Contracts\Queue\ShouldQueue;

class AfterAdviceIsCreated implements ShouldQueue
{
    use InteractsWithQueue;

    public function __construct() {}

    public function handle(AdviceCreated $event)
    {
        SendNewAdviceInfoToAdvisors::dispatch($event->advice);
    }
}
