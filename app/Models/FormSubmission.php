<?php

declare(strict_types=1);

namespace App\Models;

use App\Contracts\Pointable;
use App\Data\FormTargetPayload;
use App\Enums\FieldType;
use App\Models\Traits\HasUuid;
use App\Traits\HasPoints;
use Database\Factories\FormSubmissionFactory;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use Override;

/**
 * @property int $group_id
 * @property int|null $advice_id
 * @property Carbon $submitted_at
 * @property Carbon|null $confirmed_at
 * @property Carbon|null $confirmation_expires_at
 * @property FormTargetPayload|null $target_payload
 * @property Carbon|null $targets_failed_at
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
     * @return HasMany<SubmissionField, $this>
     */
    public function submissionFields(): HasMany
    {
        return $this->hasMany(SubmissionField::class)->orderBy('sort_order');
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
