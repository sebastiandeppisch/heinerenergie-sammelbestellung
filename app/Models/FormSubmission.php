<?php

declare(strict_types=1);

namespace App\Models;

use App\Contracts\Pointable;
use App\Data\FormTargetNoticeData;
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
use Illuminate\Support\Collection;
use Override;

/**
 * @property int $group_id
 * @property int|null $advice_id
 * @property Carbon $submitted_at
 * @property Carbon|null $confirmed_at
 * @property Carbon|null $confirmation_expires_at
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
    ];

    protected $hidden = [
        'confirmation_token_hash',
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

    /**
     * Runs the targets of the form. Targets may return notices that are shown to the submitter afterwards.
     *
     * @return Collection<int, FormTargetNoticeData>
     */
    public function handleCreators(): Collection
    {
        $notices = new Collection;
        $adviceCreator = $this->formDefinition->adviceCreator;

        if ($adviceCreator && $adviceCreator->shouldCreateFor($this)) {
            $advice = $adviceCreator->createAdvice($this);
            $this->update([
                'advice_id' => $advice->id,
            ]);
        }

        if ($this->formDefinition->mapPointCreator) {
            $this->formDefinition->mapPointCreator->createMapPoint($this);
        }

        return $notices;
    }
}
