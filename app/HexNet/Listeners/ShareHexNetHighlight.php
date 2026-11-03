<?php

declare(strict_types=1);

namespace App\HexNet\Listeners;

use App\Events\MapPointCreatedByFormSubmission;
use App\HexNet\Data\HexNetHighlightData;
use App\HexNet\Data\HexNetPublicData;
use App\HexNet\Services\HexNetGridService;
use App\HexNet\Services\HexNetJitter;
use App\HexNet\Services\HexNetResolver;
use App\Services\MapPointEditLinkService;
use App\Services\MapPointFollowUpService;
use App\Services\PublicMapPointPresenter;
use Inertia\Inertia;
use Throwable;

/**
 * When a confirmed submission creates a point of an active HexaNetz, the page after the confirmation shows its cell.
 */
class ShareHexNetHighlight
{
    public function __construct(
        private readonly HexNetResolver $resolver,
        private readonly HexNetGridService $gridService,
        private readonly HexNetJitter $jitter,
        private readonly MapPointFollowUpService $followUps,
        private readonly MapPointEditLinkService $editLinks,
    ) {}

    /**
     * The point is created inside the core transaction, so a failure here must never abort it.
     */
    public function handle(MapPointCreatedByFormSubmission $event): void
    {
        try {
            $this->share($event);
        } catch (Throwable $exception) {
            report($exception);
        }
    }

    private function share(MapPointCreatedByFormSubmission $event): void
    {
        $mapPoint = $event->mapPoint;
        $hexNet = $this->resolver->forMapPoint($mapPoint);
        $grid = $hexNet?->grid();

        if ($hexNet === null || $grid === null) {
            return;
        }

        $counts = $this->gridService->counts($hexNet);
        // The token of the event is the one the mail carries, issuing another would make that link invalid.
        $followUpLabel = $event->editToken === null ? null : $this->followUps->buttonTextFor($mapPoint);

        Inertia::share('hexNetHighlight', new HexNetHighlightData(
            hex_net: HexNetPublicData::fromModel($hexNet),
            cells: $this->gridService->cellsGeoJson($hexNet),
            counts: $counts,
            stats: $this->gridService->stats($hexNet, $counts),
            cell: $grid->cellOf($mapPoint->coordinate),
            coordinate: $this->jitter->apply($mapPoint->coordinate, $mapPoint->uuid, $hexNet->jitter_m),
            public_url: route('hexnet.public', $hexNet->slug),
            published: app(PublicMapPointPresenter::class)->isVisible($mapPoint),
            follow_up_url: $followUpLabel === null || $event->editToken === null ? null : $this->editLinks->followUpUrl($event->editToken),
            follow_up_label: $followUpLabel,
        ));
    }
}
