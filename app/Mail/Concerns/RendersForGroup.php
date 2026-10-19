<?php

declare(strict_types=1);

namespace App\Mail\Concerns;

use App\Context\FixedGroupContext;
use App\Context\GroupContextContract;
use App\Models\Group;
use Illuminate\Mail\Mailable;

/**
 * Renders the mail with the name and logo of the group, also when it is sent from the queue.
 *
 * @mixin Mailable
 */
trait RendersForGroup
{
    protected function renderForGroup(Group $group): void
    {
        $previousContext = app()->bound(GroupContextContract::class)
            ? app(GroupContextContract::class)
            : null;

        app()->instance(GroupContextContract::class, new FixedGroupContext($group));

        $this->withSymfonyMessage(function () use ($previousContext): void {
            if ($previousContext !== null) {
                app()->instance(GroupContextContract::class, $previousContext);
            } else {
                app()->forgetInstance(GroupContextContract::class);
            }
        });
    }
}
