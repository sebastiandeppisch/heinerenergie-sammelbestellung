<?php

declare(strict_types=1);

namespace App\HexNet\Listeners;

use App\Events\MapPointCreatedByFormSubmission;
use App\HexNet\Data\HexNetHighlightData;
use App\HexNet\Data\HexNetPublicData;
use App\HexNet\Services\HexNetGridService;
use App\HexNet\Services\HexNetJitter;
use App\HexNet\Services\HexNetResolver;
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

        Inertia::share('hexNetHighlight', new HexNetHighlightData(
            hex_net: HexNetPublicData::fromModel($hexNet),
            cells: $this->gridService->cellsGeoJson($hexNet),
            counts: $counts,
            stats: $this->gridService->stats($hexNet, $counts),
            cell: $grid->cellOf($mapPoint->coordinate),
            coordinate: $this->jitter->apply($mapPoint->coordinate, $mapPoint->uuid, $hexNet->jitter_m),
            public_url: route('hexnet.public', $hexNet->slug),
        ));
    }
}
