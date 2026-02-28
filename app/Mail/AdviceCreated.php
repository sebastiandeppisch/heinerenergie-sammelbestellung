<?php

namespace App\Mail;

use App\Context\FixedGroupContext;
use App\Context\GroupContextContract;
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
        $group = $advice->group;
        $this->adviceInfo = $group->new_advice_mail ?? (string) Setting::get('newAdviceMail');
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        $previousContext = app()->bound(GroupContextContract::class)
            ? app(GroupContextContract::class)
            : null;

        app()->instance(GroupContextContract::class, new FixedGroupContext($this->advice->group));

        $this->withSymfonyMessage(function () use ($previousContext) {
            if ($previousContext !== null) {
                app()->instance(GroupContextContract::class, $previousContext);
            } else {
                app()->forgetInstance(GroupContextContract::class);
            }
        });

        $this->storeClassName()->associateWith($this->advice);

        return $this->markdown('emails.advicecreated')->subject(app_name().' Beratungsanfrage');
    }
}
