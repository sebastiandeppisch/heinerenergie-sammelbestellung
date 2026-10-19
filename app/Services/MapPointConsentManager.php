<?php

declare(strict_types=1);

namespace App\Services;

use App\Data\ConsentChoiceData;
use App\Enums\PublicConsent;
use App\Models\FormSubmission;
use App\Models\MapPoint;
use Illuminate\Support\Facades\DB;

/**
 * What the person behind a point may change on the manage page, and admins in part on their behalf.
 */
class MapPointConsentManager
{
    public function __construct(private readonly MapPointConsentService $consents) {}

    /**
     * Text and keys are built again for the point's current category and characteristics.
     */
    public function change(MapPoint $mapPoint, ConsentChoiceData $choice): void
    {
        $consent = $this->consents->forPoint($mapPoint, $choice);

        $mapPoint->update([
            'public_consent' => $consent->consent,
            'public_consent_at' => now(),
            'public_consent_text' => $consent->text ?? $mapPoint->public_consent_text,
            'public_consent_fields' => $consent->fields,
        ]);
    }

    /**
     * The text of the withdrawn consent is kept, it shows what was withdrawn.
     */
    public function refuse(MapPoint $mapPoint): void
    {
        $mapPoint->update([
            'public_consent' => PublicConsent::REFUSED,
            'public_consent_at' => now(),
            'public_consent_fields' => null,
        ]);
    }

    /**
     * Deletes the point with its values and images, and the submission it was created from, so nothing personal
     * is left.
     */
    public function delete(MapPoint $mapPoint): void
    {
        DB::transaction(function () use ($mapPoint): void {
            $submission = $mapPoint->pointable;
            $mapPoint->delete();

            if ($submission instanceof FormSubmission) {
                $submission->points()->get()->each->delete();
                $submission->delete();
            }
        });
    }
}
