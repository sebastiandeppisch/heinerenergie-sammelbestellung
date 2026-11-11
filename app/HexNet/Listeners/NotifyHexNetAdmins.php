<?php

declare(strict_types=1);

namespace App\HexNet\Listeners;

use App\Events\MapPointCreatedByFormSubmission;
use App\HexNet\Mail\HexNetStoneCreated;
use App\HexNet\Services\HexNetModeration;
use App\HexNet\Services\HexNetResolver;
use App\Models\User;
use Illuminate\Contracts\Events\ShouldHandleEventsAfterCommit;
use Illuminate\Support\Facades\Mail;
use Throwable;

/**
 * Tells the admins of the campaign's group about a new stepping stone of an active campaign, after the commit, so the
 * mail shows whether it was published right away. Additions through the follow-up form create no point and no mail.
 */
class NotifyHexNetAdmins implements ShouldHandleEventsAfterCommit
{
    public function __construct(private readonly HexNetResolver $resolver, private readonly HexNetModeration $moderation) {}

    public function handle(MapPointCreatedByFormSubmission $event): void
    {
        try {
            $mapPoint = $event->mapPoint->fresh();
            $hexNet = $mapPoint === null ? null : $this->resolver->forMapPoint($mapPoint);

            if ($mapPoint === null || $hexNet === null) {
                return;
            }

            $hexNet->loadMissing('group');
            $point = $this->moderation->describe($hexNet, $mapPoint);

            $hexNet->group->admins()->get()->each(function (User $admin) use ($hexNet, $mapPoint, $point): void {
                $actions = [];

                foreach ($this->moderation->actionsFor($mapPoint) as $action) {
                    $actions[$action->label()] = $this->moderation->url($hexNet, $mapPoint, $admin, $action);
                }

                Mail::to($admin)->send(new HexNetStoneCreated($hexNet->group, $point, $actions));
            });
        } catch (Throwable $exception) {
            report($exception);
        }
    }
}
