<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Events\MapPointCreatedByFormSubmission;
use App\Services\MapPointEditLinkService;
use Illuminate\Contracts\Events\ShouldHandleEventsAfterCommit;

/**
 * A later target may still fail and roll the point back, so the mail leaves only after the commit.
 */
class SendMapPointCreatedMail implements ShouldHandleEventsAfterCommit
{
    public function __construct(private readonly MapPointEditLinkService $editLinks) {}

    public function handle(MapPointCreatedByFormSubmission $event): void
    {
        $this->editLinks->sendCreated($event->mapPoint);
    }
}
