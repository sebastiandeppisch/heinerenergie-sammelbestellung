<?php

declare(strict_types=1);

namespace App\HexNet\Mail;

use App\HexNet\Data\HexNetModerationPointData;
use App\Mail\Concerns\RendersForGroup;
use App\Models\Group;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeEncrypted;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

/**
 * Tells an admin of the campaign about a new stepping stone. Carries signed action links, so the queued payload is
 * encrypted and the mail is not stored as a send.
 */
class HexNetStoneCreated extends Mailable implements ShouldBeEncrypted, ShouldQueue
{
    use Queueable;
    use RendersForGroup;
    use SerializesModels;

    /**
     * @param  array<string, string>  $actions  link by label
     */
    public function __construct(public Group $group, public HexNetModerationPointData $point, public array $actions) {}

    public function build(): self
    {
        $this->renderForGroup($this->group);

        return $this->markdown('emails.hexnet.stone-created')->subject($this->point->campaign.': Neuer Trittstein „'.$this->point->title.'“');
    }
}
