<?php

declare(strict_types=1);

namespace App\HexNet\Listeners;

use App\Events\MapPointCreatedByFormSubmission;
use App\HexNet\Services\HexNetResolver;
use Throwable;

/**
 * A HexaNetz can publish new stepping stones right away instead of waiting for an admin. The consent still decides
 * whether the point is visible.
 */
class PublishHexNetPoint
{
    public function __construct(private readonly HexNetResolver $resolver) {}

    public function handle(MapPointCreatedByFormSubmission $event): void
    {
        try {
            if ($this->resolver->forMapPoint($event->mapPoint)?->auto_publish === true) {
                $event->mapPoint->update(['published' => true]);
            }
        } catch (Throwable $exception) {
            report($exception);
        }
    }
}
