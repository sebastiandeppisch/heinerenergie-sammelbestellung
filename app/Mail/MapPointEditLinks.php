<?php

declare(strict_types=1);

namespace App\Mail;

use App\Mail\Concerns\RendersForGroup;
use App\Models\Group;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeEncrypted;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Wnx\Sends\Support\StoreMailables;

/**
 * Carries plain manage links, so the queued payload is encrypted and the content is not stored as a send.
 */
class MapPointEditLinks extends Mailable implements ShouldBeEncrypted, ShouldQueue
{
    use Queueable;
    use RendersForGroup;
    use SerializesModels;
    use StoreMailables;

    /**
     * @param  array<int, array{title: string, url: string}>  $links
     */
    public function __construct(public Group $group, public array $links) {}

    public function build(): self
    {
        $this->renderForGroup($this->group);
        $this->storeClassName();

        return $this->markdown('emails.mappointeditlinks')->subject(app_name().': Deine Einträge auf der Karte verwalten');
    }
}
