<?php

declare(strict_types=1);

namespace App\HexNet\Http\Controllers;

use App\HexNet\Enums\HexNetModerationAction;
use App\HexNet\Models\HexNet;
use App\HexNet\Services\HexNetModeration;
use App\Http\Controllers\Controller;
use App\Models\MapPoint;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * The page behind a button in the mail about a new stepping stone. Opening it changes nothing, only the confirmation
 * does. Expired or invalid links and admins who lost their role end up at the point in the backend.
 */
class HexNetModerationController extends Controller
{
    public function __construct(private readonly HexNetModeration $moderation) {}

    public function show(Request $request, string $hexNet, string $mapPoint, string $action, string $admin): Response|RedirectResponse
    {
        $resolved = $this->resolve($request, $hexNet, $mapPoint, $action, $admin);

        if ($resolved instanceof RedirectResponse) {
            return $resolved;
        }

        [$campaign, $point, $moderationAction] = $resolved;

        return Inertia::render('HexNet/Moderation', [
            'point' => $this->moderation->describe($campaign, $point),
            'action' => $moderationAction,
            'actionLabel' => $moderationAction->label(),
            'confirmUrl' => $request->fullUrl(),
            'done' => false,
        ]);
    }

    public function store(Request $request, string $hexNet, string $mapPoint, string $action, string $admin): Response|RedirectResponse
    {
        $resolved = $this->resolve($request, $hexNet, $mapPoint, $action, $admin);

        if ($resolved instanceof RedirectResponse) {
            return $resolved;
        }

        [$campaign, $point, $moderationAction] = $resolved;
        $description = $this->moderation->describe($campaign, $point);
        $this->moderation->apply($point, $moderationAction);

        return Inertia::render('HexNet/Moderation', [
            'point' => $moderationAction === HexNetModerationAction::DELETE ? $description : $this->moderation->describe($campaign, $point->refresh()),
            'action' => $moderationAction,
            'actionLabel' => $moderationAction->label(),
            'confirmUrl' => null,
            'done' => true,
        ]);
    }

    /**
     * @return array{0: HexNet, 1: MapPoint, 2: HexNetModerationAction}|RedirectResponse
     */
    private function resolve(Request $request, string $hexNetUuid, string $mapPointUuid, string $action, string $adminId): array|RedirectResponse
    {
        $mapPoint = MapPoint::where('uuid', $mapPointUuid)->first();
        $fallback = $mapPoint === null ? redirect()->route('mappoints.index') : redirect()->route('mappoints.edit', $mapPoint);

        $hexNet = HexNet::where('uuid', $hexNetUuid)->first();
        $admin = User::find((int) $adminId);
        $moderationAction = HexNetModerationAction::tryFrom($action);

        if (! $request->hasValidSignature() || $mapPoint === null || $hexNet === null || $admin === null || $moderationAction === null) {
            return $fallback->with('info', $mapPoint === null ? 'Den Trittstein gibt es nicht mehr.' : 'Der Link ist abgelaufen. Hier kannst du den Trittstein bearbeiten.');
        }

        if (! $this->moderation->allows($hexNet, $mapPoint, $admin)) {
            return $fallback->with('info', 'Du bist nicht mehr Admin dieser Kampagne.');
        }

        return [$hexNet, $mapPoint, $moderationAction];
    }
}
