<?php

namespace App\Listeners;

use App\Events\OrderCreated;
use App\Mail\OrderCreated as OrderCreatedMail;
use App\Mail\OrderCreatedAdvisor;
use Illuminate\Support\Facades\Mail;

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
     * @return void
     */
    public function handle(OrderCreated $event)
    {
        $order = $event->order;
        Mail::to($order->email)->send(new OrderCreatedMail($order));
        Mail::to($order->advisor->email)->send(new OrderCreatedAdvisor($order));
    }
}
