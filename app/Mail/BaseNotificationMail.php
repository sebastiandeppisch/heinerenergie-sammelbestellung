<?php

namespace App\Mail;

use Illuminate\Notifications\Messages\MailMessage;
use Wnx\Sends\Support\StoreMailables;

class BaseNotificationMail extends MailMessage
{
    use StoreMailables;
}
