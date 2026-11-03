<?php

declare(strict_types=1);

namespace App\Models;

use App\Contracts\Pointable;
use App\Data\FormTargetPayload;
use App\Enums\FieldType;
use App\Enums\MapPointDeletedBy;
use App\Models\Traits\HasUuid;
use App\Services\ImageStorage;
use App\Traits\HasPoints;
use Database\Factories\FormSubmissionFactory;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Override;

/**
 * @property int $group_id
 * @property int|null $advice_id
 * @property Carbon $submitted_at
 * @property Carbon|null $confirmed_at
 * @property Carbon|null $confirmation_expires_at
 * @property FormTargetPayload|null $target_payload
 * @property Carbon|null $targets_failed_at
 * @property Carbon|null $map_point_deleted_at
 * @property MapPointDeletedBy|null $map_point_deleted_by
 *
 * @implements Pointable<self>
 */
class FormSubmission extends Model implements Pointable
{
    /** @use HasFactory<FormSubmissionFactory> */
    use HasFactory;

    /** @use HasPoints<self> */
    use HasPoints;

    use HasUuid;

    protected $fillable = [
        'form_definition_id',
        'advice_id',
        'form_name',
        'form_description',
        'submitted_at',
        'group_id',
        'confirmed_at',
        'confirmation_token_hash',
        'confirmation_expires_at',
        'target_payload',
        'targets_failed_at',
        'targets_error',
        'map_point_deleted_at',
        'map_point_deleted_by',
        'map_point_id',
    ];

    protected $hidden = [
        'confirmation_token_hash',
        'target_payload',
    ];

    #[Override]
    /**
     * @return array<string, string>
     */
    public function casts()
    {
        return [
            'submitted_at' => 'datetime',
            'seen' => 'boolean',
            'confirmed_at' => 'datetime',
            'confirmation_expires_at' => 'datetime',
            'target_payload' => FormTargetPayload::class,
            'targets_failed_at' => 'datetime',
            'map_point_deleted_at' => 'datetime',
            'map_point_deleted_by' => MapPointDeletedBy::class,
        ];
    }

    /**
     * @return BelongsTo<FormDefinition, $this>
     */
    public function formDefinition(): BelongsTo
    {
        return $this->belongsTo(FormDefinition::class);
    }

    /**
     * @return BelongsTo<Group, $this>
     */
    public function group(): BelongsTo
    {
        return $this->belongsTo(Group::class);
    }

    /**
     * The advice created from this submission.
     *
     * @return BelongsTo<Advice, $this>
     */
    public function advice(): BelongsTo
    {
        return $this->belongsTo(Advice::class);
    }

    /**
     * The existing point a follow-up form added to. Points a form created are linked through HasPoints.
     *
     * @return BelongsTo<MapPoint, $this>
     */
    public function mapPoint(): BelongsTo
    {
        return $this->belongsTo(MapPoint::class);
    }

    /**
     * @return HasMany<SubmissionField, $this>
     */
    public function submissionFields(): HasMany
    {
        return $this->hasMany(SubmissionField::class)->orderBy('sort_order');
    }

    /**
     * The submitted values and uploaded images go with the submission. The foreign keys do not cascade, so they are
     * deleted here.
     */
    #[Override]
    public function delete(): ?bool
    {
        return DB::transaction(function (): ?bool {
            $fieldIds = SubmissionField::where('form_submission_id', $this->id)->pluck('id');
            SubmissionFieldOption::whereIn('submission_field_id', $fieldIds)->delete();
            SubmissionField::whereIn('id', $fieldIds)->delete();
            DB::afterCommit(fn () => app(ImageStorage::class)->deleteDirectory($this->imageDirectory()));

            return parent::delete();
        });
    }

    public function imageDirectory(): string
    {
        return 'form-images/'.$this->uuid;
    }

    /**
     * The submission is kept when its point is deleted, e.g. for an advice it also asked for. It notes who deleted it.
     */
    public function noteMapPointDeleted(MapPointDeletedBy $deletedBy): void
    {
        $this->update(['map_point_deleted_at' => now(), 'map_point_deleted_by' => $deletedBy]);
    }

    public function isAwaitingConfirmation(): bool
    {
        return $this->confirmation_token_hash !== null && $this->confirmed_at === null;
    }

    public function isConfirmationExpired(): bool
    {
        return $this->confirmation_expires_at !== null && $this->confirmation_expires_at->isPast();
    }

    /**
     * The submitted address the confirmation mail is sent to.
     */
    public function confirmationEmail(): ?string
    {
        $value = $this->submissionFields->firstWhere('type', FieldType::EMAIL)?->value;

        return is_string($value) && $value !== '' ? $value : null;
    }

    /**
     * @param  Builder<FormSubmission>  $query
     */
    #[Scope]
    protected function unconfirmed(Builder $query): void
    {
        $query->whereNotNull('confirmation_token_hash')->whereNull('confirmed_at');
    }

    /**
     * @param  Builder<FormSubmission>  $query
     */
    #[Scope]
    protected function withoutUnconfirmed(Builder $query): void
    {
        $query->where(fn (Builder $query) => $query->whereNull('confirmation_token_hash')->orWhereNotNull('confirmed_at'));
    }
}
