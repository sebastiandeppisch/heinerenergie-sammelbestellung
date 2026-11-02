<?php

declare(strict_types=1);

namespace App\HexNet\Http\Controllers;

use App\HexNet\Models\HexNet;
use App\HexNet\Services\HexNetGridService;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreMapPointContactRequest;
use App\Models\MapPoint;
use App\Services\CurrentGroupService;
use App\Services\MapPointContactService;
use App\Services\PublicMapPointPresenter;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Exceptions\ThrottleRequestsException;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Contact to a stepping stone through the campaign page. Uses the contact flow of the map points.
 */
class HexNetContactController extends Controller
{
    public function __construct(private readonly MapPointContactService $contact, private readonly HexNetGridService $gridService) {}

    public function create(Request $request, string $slug, MapPoint $mapPoint): Response
    {
        $hexNet = $this->abortUnlessContactable($request, $slug, $mapPoint);

        return Inertia::render('MapPointContact/Create', [
            'mapPoint' => ['id' => $mapPoint->uuid, 'title' => app(PublicMapPointPresenter::class)->title($mapPoint)],
            'messageMaxLength' => (int) config('map_contact.message_max_length'),
            'storeUrl' => route('hexnet.contact.store', [$hexNet->slug, $mapPoint]),
            'backUrl' => route('hexnet.public', $hexNet->slug),
        ]);
    }

    public function store(StoreMapPointContactRequest $request, string $slug, MapPoint $mapPoint): Response
    {
        $hexNet = $this->abortUnlessContactable($request, $slug, $mapPoint);

        if (! $request->isSpam()) {
            try {
                $this->contact->ensureContactNotRateLimited((string) $request->ip(), $request->validated('email'), $mapPoint);
            } catch (ThrottleRequestsException $exception) {
                throw ValidationException::withMessages(['message' => $exception->getMessage()]);
            }

            $this->contact->createRequest($mapPoint, $request->validated('name'), $request->validated('email'), $request->validated('message'));
        }

        return Inertia::render('MapPointContact/Sent', [
            'expiresHours' => (int) config('map_contact.confirmation_expires_hours'),
            'backUrl' => route('hexnet.public', $hexNet->slug),
        ]);
    }

    /**
     * The point has to be a visible stepping stone of the HexaNetz and accept contact, otherwise it is not found.
     */
    private function abortUnlessContactable(Request $request, string $slug, MapPoint $mapPoint): HexNet
    {
        $hexNet = HexNet::where('slug', $slug)->with('group')->firstOrFail();
        abort_unless($hexNet->isActive() || $request->user()?->can('update', $hexNet), 404);
        app(CurrentGroupService::class)->setGroup($hexNet->group);

        $isStone = $this->gridService
            ->pointsInPlayArea($hexNet, ['id', 'lat', 'lng'], /** @param Builder<MapPoint> $query */ function (Builder $query) use ($mapPoint): void {
                $query->whereKey($mapPoint->getKey());
            })
            ->isNotEmpty();

        abort_unless($isStone && app(PublicMapPointPresenter::class)->isVisible($mapPoint) && $mapPoint->hasContact(), 404);

        return $hexNet;
    }
}
