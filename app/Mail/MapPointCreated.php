<?php

declare(strict_types=1);

namespace App\Mail;

use App\Mail\Concerns\RendersForGroup;
use App\Models\MapPoint;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeEncrypted;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Wnx\Sends\Support\StoreMailables;

/**
 * Carries the plain manage link, so the queued payload is encrypted and the content is not stored as a send.
 */
class MapPointCreated extends Mailable implements ShouldBeEncrypted, ShouldQueue
{
    use Queueable;
    use RendersForGroup;
    use SerializesModels;
    use StoreMailables;

    public function __construct(public MapPoint $mapPoint, public string $manageUrl) {}

    public function build(): self
    {
        $this->renderForGroup($this->mapPoint->group);
        $this->storeClassName();

        return $this->markdown('emails.mappointcreated')->subject(app_name().': Dein Eintrag ist da');
    }
}
