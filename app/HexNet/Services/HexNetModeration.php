<?php

declare(strict_types=1);

namespace App\HexNet\Services;

use App\Enums\MapPointDeletedBy;
use App\Enums\PublicConsent;
use App\HexNet\Data\HexNetModerationPointData;
use App\HexNet\Enums\HexNetModerationAction;
use App\HexNet\Models\HexNet;
use App\Models\MapPoint;
use App\Models\MapPointCharacteristic;
use App\Models\User;
use App\Services\MapPointConsentService;
use Illuminate\Support\Facades\URL;

/**
 * Links in the mail about a new stepping stone. Each link is signed for one admin, one point and one action and only
 * leads to a page that asks for confirmation, because mail scanners open links.
 */
class HexNetModeration
{
    public const int LINK_DAYS = 14;

    public function __construct(private readonly MapPointConsentService $consents) {}

    /**
     * Publishing for hidden points, withdrawing for visible ones, deleting against spam.
     *
     * @return array<int, HexNetModerationAction>
     */
    public function actionsFor(MapPoint $mapPoint): array
    {
        return [$mapPoint->published ? HexNetModerationAction::WITHDRAW : HexNetModerationAction::PUBLISH, HexNetModerationAction::DELETE];
    }

    public function url(HexNet $hexNet, MapPoint $mapPoint, User $admin, HexNetModerationAction $action): string
    {
        return URL::temporarySignedRoute('hexnet.moderation.show', now()->addDays(self::LINK_DAYS), [
            'hexNet' => $hexNet->uuid,
            'mapPoint' => $mapPoint->uuid,
            'action' => $action->value,
            'admin' => $admin->id,
        ]);
    }

    /**
     * The person must still be an admin of the campaign's group and the point still one of its stepping stones.
     */
    public function allows(HexNet $hexNet, MapPoint $mapPoint, User $admin): bool
    {
        $hexNet->loadMissing('group');

        return $hexNet->group->admins()->whereKey($admin->id)->exists()
            && in_array((int) $mapPoint->category_id, app(HexNetGridService::class)->categoryIds($hexNet), true)
            && in_array((int) $mapPoint->group_id, array_map(intval(...), $hexNet->group->getSubtreeIds()), true);
    }

    public function apply(MapPoint $mapPoint, HexNetModerationAction $action): void
    {
        match ($action) {
            HexNetModerationAction::PUBLISH => $mapPoint->update(['published' => true]),
            HexNetModerationAction::WITHDRAW => $mapPoint->update(['published' => false]),
            HexNetModerationAction::DELETE => $mapPoint->deleteBy(MapPointDeletedBy::ADMIN),
        };
    }

    public function describe(HexNet $hexNet, MapPoint $mapPoint): HexNetModerationPointData
    {
        $mapPoint->loadMissing('category', 'characteristics');

        return new HexNetModerationPointData(
            campaign: $hexNet->title ?: $hexNet->name,
            title: trim((string) $mapPoint->title) !== '' ? (string) $mapPoint->title : ($mapPoint->category->name ?? 'Trittstein'),
            category: $mapPoint->category?->name,
            measures: $mapPoint->characteristics->map(fn (MapPointCharacteristic $measure): string => $measure->name)->values()->all(),
            consent: $this->consentLabel($mapPoint),
            published: $mapPoint->published,
            created_at: $mapPoint->created_at->timezone(config('app.timezone'))->format('d.m.Y H:i'),
            backend_url: route('mappoints.edit', $mapPoint),
        );
    }

    private function consentLabel(MapPoint $mapPoint): string
    {
        $details = array_values(array_diff($mapPoint->public_consent_fields ?? [], [MapPointConsentService::COORDINATE]));

        return match ($mapPoint->public_consent) {
            PublicConsent::REFUSED => 'Nur mitzählen, nicht öffentlich',
            PublicConsent::NOT_REQUIRED => 'Nicht erforderlich',
            PublicConsent::GIVEN => $details === [] ? 'Nur als Punkt auf der Karte' : 'Mit Angaben: '.implode(', ', $this->consents->labelsOf($details)),
        };
    }
}
