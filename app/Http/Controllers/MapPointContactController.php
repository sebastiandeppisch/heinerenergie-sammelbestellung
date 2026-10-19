<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\StoreMapPointContactRequest;
use App\Models\MapEmbed;
use App\Models\MapPoint;
use App\Models\MapPointContactRequest;
use App\Services\CurrentGroupService;
use App\Services\MapPointContactService;
use Illuminate\Http\Exceptions\ThrottleRequestsException;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class MapPointContactController extends Controller
{
    public function __construct(private readonly MapPointContactService $contact) {}

    public function create(MapEmbed $mapEmbed, MapPoint $mapPoint): Response
    {
        $this->abortUnlessContactable($mapEmbed, $mapPoint);

        return Inertia::render('MapPointContact/Create', [
            'mapEmbedId' => $mapEmbed->uuid,
            'mapPoint' => ['id' => $mapPoint->uuid, 'title' => $mapPoint->title],
            'initiative' => $mapPoint->group->name,
            'messageMaxLength' => (int) config('map_contact.message_max_length'),
        ]);
    }

    public function store(StoreMapPointContactRequest $request, MapEmbed $mapEmbed, MapPoint $mapPoint): Response
    {
        $this->abortUnlessContactable($mapEmbed, $mapPoint);

        if (! $request->isSpam()) {
            try {
                $this->contact->ensureContactNotRateLimited((string) $request->ip(), $request->validated('email'), $mapPoint);
            } catch (ThrottleRequestsException $exception) {
                throw ValidationException::withMessages(['message' => $exception->getMessage()]);
            }

            $this->contact->createRequest(
                $mapPoint,
                $request->validated('name'),
                $request->validated('email'),
                $request->validated('message'),
            );
        }

        return Inertia::render('MapPointContact/Sent', [
            'mapEmbedId' => $mapEmbed->uuid,
            'expiresHours' => (int) config('map_contact.confirmation_expires_hours'),
        ]);
    }

    public function showConfirmation(string $token): Response
    {
        $contactRequest = $this->contact->findRequestByToken($token);

        return $this->confirmationPage($contactRequest, $this->confirmationStatus($contactRequest), $token);
    }

    public function confirm(string $token): Response
    {
        $contactRequest = $this->contact->findRequestByToken($token);
        $status = $this->confirmationStatus($contactRequest);

        if ($status === 'pending' && $contactRequest !== null) {
            if (! $this->contact->confirm($contactRequest)) {
                $status = 'already_confirmed';
            } else {
                $status = $contactRequest->forwarded_at !== null ? 'forwarded' : 'unavailable';
            }
        }

        return $this->confirmationPage($contactRequest, $status, $token);
    }

    private function confirmationStatus(?MapPointContactRequest $contactRequest): string
    {
        return match (true) {
            $contactRequest === null => 'invalid',
            $contactRequest->isConfirmed() => 'already_confirmed',
            $contactRequest->isConfirmationExpired() => 'expired',
            default => 'pending',
        };
    }

    private function confirmationPage(?MapPointContactRequest $contactRequest, string $status, string $token): Response
    {
        if ($contactRequest !== null) {
            app(CurrentGroupService::class)->setGroup($contactRequest->mapPoint->group);
        }

        return Inertia::render('MapPointContact/Confirm', [
            'status' => $status,
            'token' => $status === 'pending' ? $token : null,
            'title' => $contactRequest?->mapPoint->title,
        ]);
    }

    /**
     * The point has to be shown on this embed and accept contact, otherwise it is not found.
     */
    private function abortUnlessContactable(MapEmbed $mapEmbed, MapPoint $mapPoint): void
    {
        app(CurrentGroupService::class)->setGroup($mapEmbed->group);

        $shownOnEmbed = MapPoint::query()
            ->visibleFromGroup($mapEmbed->group)
            ->whereKey($mapPoint->getKey())
            ->whereIn('category_id', $mapEmbed->mapPointCategories()->pluck('map_point_categories.id'))
            ->exists();

        abort_unless($shownOnEmbed && $mapPoint->hasContact(), 404);
    }
}
