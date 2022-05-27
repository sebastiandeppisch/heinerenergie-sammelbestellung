<?php

namespace App\Listeners;

use App\Events\OrderCreated;
use App\Mail\OrderCreatedAdvisor;
use Illuminate\Support\Facades\Mail;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use App\Mail\OrderCreated as OrderCreatedMail;

class SendOrderCreatedNotification
{
    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     *
     * @param  \App\Events\OrderCreated  $event
     * @return void
     */
    public function handle(OrderCreated $event)
    {
        $order = $event->order;
        Mail::to($order->email)->send(new OrderCreatedMail($order));
        Mail::to($order->advisor->email)->send(new OrderCreatedAdvisor($order));
    }
}
