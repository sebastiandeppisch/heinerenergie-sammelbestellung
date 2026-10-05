<?php

declare(strict_types=1);

namespace App\Services;

use App\Data\AdviceTargetPayload;
use App\Data\FormTargetNoticeData;
use App\Data\FormTargetPayload;
use App\Data\MapPointTargetPayload;
use App\Enums\FieldType;
use App\Events\Advice\AdviceCreatedByFormSubmission;
use App\Events\MapPointCreatedByFormSubmission;
use App\Jobs\SendNewAdviceInfoToAdvisors;
use App\Mail\AdviceCreated;
use App\Models\Advice;
use App\Models\FormField;
use App\Models\FormSubmission;
use App\Models\MapPoint;
use App\Models\MapPointCategory;
use App\Models\User;
use App\Notifications\SystemErrorNotification;
use App\ValueObjects\StoredImage;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

/**
 * Runs the targets of a form (advice, map point). What they create is read from the form when the submission is
 * stored, so a submission waiting for its confirmation is not affected by later changes to the form.
 */
class FormTargetService
{
    public function __construct(private readonly MapPointFieldService $mapPointFields) {}

    public function prepare(FormSubmission $submission): FormTargetPayload
    {
        $formDefinition = $submission->formDefinition;

        return new FormTargetPayload(
            advice: $formDefinition->adviceCreator?->prepare($submission),
            map_point: $formDefinition->mapPointCreator?->prepare($submission),
        );
    }

    /**
     * Like prepare, but a failure leaves the payload empty, so the targets are prepared again when they run.
     */
    public function prepareOrNull(FormSubmission $submission): ?FormTargetPayload
    {
        try {
            return DB::transaction(fn (): FormTargetPayload => $this->prepare($submission));
        } catch (Throwable $e) {
            report($e);

            return null;
        }
    }

    /**
     * Creates all targets or none. A failure keeps the submission, marks it and notifies the system admins.
     *
     * @return Collection<int, FormTargetNoticeData>
     */
    public function run(FormSubmission $submission, ?FormTargetPayload $payload): Collection
    {
        try {
            return DB::transaction(fn (): Collection => $this->execute($submission, $payload ?? $this->prepare($submission)));
        } catch (Throwable $e) {
            report($e);
            $submission->refresh()->update(['targets_failed_at' => now(), 'targets_error' => $e->getMessage()]);
            $this->notifyAdmins($submission, $e);

            return new Collection;
        }
    }

    /**
     * @return Collection<int, FormTargetNoticeData>
     */
    public function execute(FormSubmission $submission, FormTargetPayload $payload): Collection
    {
        if ($payload->advice !== null) {
            $this->createAdvice($submission, $payload->advice);
        }

        if ($payload->map_point !== null) {
            $this->createMapPoint($submission, $payload->map_point);
        }

        return new Collection;
    }

    private function createAdvice(FormSubmission $submission, AdviceTargetPayload $payload): Advice
    {
        $advice = DB::transaction(function () use ($submission, $payload): Advice {
            $advice = Advice::create([
                'address' => $payload->address,
                'email' => $payload->email,
                'phone' => $payload->phone,
                'first_name' => $payload->first_name,
                'last_name' => $payload->last_name,
                'group_id' => $payload->group_id,
                'type' => $payload->type,
            ]);

            event(new AdviceCreatedByFormSubmission($advice, $submission));

            $submission->update(['advice_id' => $advice->id]);

            return $advice->fresh() ?? $advice;
        });

        Log::info('Created advice from form submission', [
            'advice_id' => $advice->id,
            'form_submission_id' => $submission->id,
        ]);

        // A later target may still fail and roll the advice back, so nothing leaves before the commit.
        SendNewAdviceInfoToAdvisors::dispatch($advice)->afterCommit();
        Mail::to($advice->email)->send((new AdviceCreated($advice))->afterCommit());

        return $advice;
    }

    private function createMapPoint(FormSubmission $submission, MapPointTargetPayload $payload): MapPoint
    {
        return DB::transaction(function () use ($submission, $payload): MapPoint {
            $mapPoint = MapPoint::create([
                'group_id' => $payload->group_id,
                'title' => $payload->title,
                'description' => $payload->description,
                'coordinate' => $payload->coordinate,
                'published' => false,
                'category_id' => $payload->category_id === null ? null : MapPointCategory::findOrFail($payload->category_id)->id,
            ]);

            $fields = $this->mapPointFields->fieldsOfCategory($mapPoint->category_id);
            // A value that does not fit its field is left out, the point is created anyway.
            $this->mapPointFields->syncValues($mapPoint, $this->mapPointFields->validValues($fields, $this->withStoredImages($fields, $payload->field_values)));

            // @phpstan-ignore argument.type (Pointable<FormSubmission> satisfies Pointable<Model> at runtime; PHPStan invariance limitation)
            $mapPoint->pointable()->associate($submission);
            $mapPoint->save();

            event(new MapPointCreatedByFormSubmission($mapPoint, $submission));

            return $mapPoint->fresh() ?? $mapPoint;
        });
    }

    /**
     * Images of the submission are copied onto the point, so both can be deleted on their own.
     *
     * @param  EloquentCollection<int, FormField>  $fields
     * @param  array<string, mixed>  $values
     * @return array<string, mixed>
     */
    private function withStoredImages(EloquentCollection $fields, array $values): array
    {
        foreach ($fields->where('type', FieldType::IMAGE) as $field) {
            if (array_key_exists($field->uuid, $values)) {
                $values[$field->uuid] = array_map(fn (string $path): StoredImage => new StoredImage($path), (array) $values[$field->uuid]);
            }
        }

        return $values;
    }

    private function notifyAdmins(FormSubmission $submission, Throwable $e): void
    {
        $notification = new SystemErrorNotification(
            'Ziele einer Formular-Einsendung fehlgeschlagen',
            $e->getMessage(),
            formSubmission: $submission,
        );

        User::where('is_admin', true)->get()->each->notify($notification);
    }
}
