<?php

declare(strict_types=1);

namespace App\HexNet\Listeners;

use App\Events\MapPointCreatedByFormSubmission;
use App\HexNet\Services\HexNetResolver;
use Throwable;

/**
 * A HexaNetz can publish new stepping stones right away instead of waiting for an admin. The consent still decides
 * whether the point is visible. Only points of its own group or subgroups, which its admins may publish anyway.
 */
class PublishHexNetPoint
{
    public function __construct(private readonly HexNetResolver $resolver) {}

    public function handle(MapPointCreatedByFormSubmission $event): void
    {
        try {
            $mapPoint = $event->mapPoint;
            $hexNet = $this->resolver->forMapPoint($mapPoint);

            if ($hexNet?->auto_publish === true && in_array((int) $mapPoint->group_id, array_map(intval(...), $hexNet->group->getSubtreeIds()), true)) {
                $mapPoint->update(['published' => true]);
            }
        } catch (Throwable $exception) {
            report($exception);
        }
    }
}
