<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Data\FormDefinitionData;
use App\Http\Requests\StoreMapPointFollowUpRequest;
use App\Models\MapPoint;
use App\Services\CurrentGroupService;
use App\Services\FormSubmissionRecorder;
use App\Services\ImageStorage;
use App\Services\MapPointConsentService;
use App\Services\MapPointEditLinkService;
use App\Services\MapPointFollowUpService;
use App\Services\MapPointImageAccess;
use Illuminate\Http\Exceptions\ThrottleRequestsException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\RateLimiter;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\Response as HttpResponse;
use Throwable;

/**
 * The follow-up form of a point, reachable with its manage link. The token proves that the person may add to the
 * point, so the form asks for no confirmation.
 */
class MapPointFollowUpController extends Controller
{
    private const int DECAY_SECONDS = 3600;

    public function __construct(
        private readonly MapPointEditLinkService $editLinks,
        private readonly MapPointFollowUpService $followUps,
        private readonly MapPointConsentService $consents,
        private readonly FormSubmissionRecorder $recorder,
        private readonly ImageStorage $imageStorage,
    ) {}

    public function show(Request $request, string $token): Response
    {
        $mapPoint = $this->editLinks->findByToken($token);

        if ($mapPoint === null) {
            return $this->invalid();
        }

        $followUp = $this->followUps->formFor($mapPoint);
        abort_if($followUp === null, 404);

        app(CurrentGroupService::class)->setGroup($mapPoint->group);

        return Inertia::render('MapPointManage/FollowUp', [
            'token' => $token,
            'proof' => $this->mailProof($request, $token),
            'title' => $mapPoint->title,
            'formDefinition' => FormDefinitionData::forPublic($followUp),
            'initialData' => (object) $this->followUps->initialData($mapPoint, $followUp),
            'storedImages' => (object) $this->followUps->storedImages($mapPoint, $followUp, $token),
        ]);
    }

    public function submit(StoreMapPointFollowUpRequest $request, string $token): Response
    {
        $mapPoint = $request->mapPoint();

        if ($mapPoint === null) {
            return $this->invalid();
        }

        $followUp = $request->followUp();
        abort_if($followUp === null, 404);

        app(CurrentGroupService::class)->setGroup($mapPoint->group);
        $this->ensureNotRateLimited($mapPoint);

        $storedImagePaths = [];

        try {
            DB::transaction(function () use ($request, $mapPoint, $followUp, &$storedImagePaths): void {
                $submission = $this->recorder->record($followUp, $request, $storedImagePaths);
                $submission->update(['map_point_id' => $mapPoint->id]);
                $this->followUps->apply($mapPoint, $submission, $request->removedImages());
            });
        } catch (Throwable $e) {
            $this->imageStorage->delete($storedImagePaths);

            throw $e;
        }

        $newLabels = $this->consents->unconsentedLabels($mapPoint->refresh());

        return Inertia::render('MapPointManage/FollowUpSubmitted', [
            'token' => $token,
            'proof' => $this->mailProof($request, $token),
            'successMessage' => $followUp->success_message ?: 'Danke, deine Angaben sind gespeichert.',
            'newDetails' => $newLabels === [] ? null : [
                'labels' => $newLabels,
                'details_text' => $this->consents->previewForPoint($mapPoint)['details_text'],
            ],
        ]);
    }

    /**
     * Images of the point for its person, also while the point is not published.
     */
    public function image(Request $request, string $token, string $image, MapPointImageAccess $access): HttpResponse
    {
        $mapPoint = $this->editLinks->findByToken($token);
        $path = $mapPoint === null ? null : $mapPoint->imageDirectory().'/'.basename($image);

        abort_if($mapPoint === null || $path === null || $access->fieldOf($mapPoint, $path) === null, 404);

        return $this->imageStorage->response($request, $path, height: $request->has('h') ? $request->integer('h') : null);
    }

    private function ensureNotRateLimited(MapPoint $mapPoint): void
    {
        $key = 'map-point-follow-up:'.$mapPoint->uuid;

        if (RateLimiter::tooManyAttempts($key, (int) config('forms.map_point_follow_up.max_per_point_per_hour'))) {
            throw new ThrottleRequestsException('Zu viele Anfragen, bitte versuche es später erneut.');
        }

        RateLimiter::hit($key, self::DECAY_SECONDS);
    }

    /**
     * The proof that the manage link came by mail is carried along, so the manage page still confirms the address.
     */
    private function mailProof(Request $request, string $token): ?string
    {
        $proof = $request->query('nachweis');

        return $this->editLinks->hasMailProof($token, $proof) ? (string) $proof : null;
    }

    private function invalid(): Response
    {
        return Inertia::render('MapPointManage/Message', ['status' => 'invalid']);
    }
}
