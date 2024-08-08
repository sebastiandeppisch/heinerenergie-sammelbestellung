<?php

namespace App\Mail;

use App\Models\Advice;
use App\Models\Setting;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Wnx\Sends\Support\StoreMailables;

class AdviceCreated extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels, StoreMailables;

    public string $adviceInfo;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct(public Advice $advice)
    {
        $this->adviceInfo = (string) Setting::get('newAdviceMail');
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        $this->storeClassName()->associateWith($this->advice);
        return $this->markdown('emails.advicecreated')->subject('heiner*energie Beratungsanfrage');
    }
}
