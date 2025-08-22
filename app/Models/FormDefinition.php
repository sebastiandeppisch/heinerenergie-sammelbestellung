<?php

namespace App\Models;

use App\Models\Traits\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Facades\DB;

class FormDefinition extends Model
{
    use HasFactory;
    use HasUuid;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string, mixed>
     */
    protected $fillable = [
        'name',
        'description',
        'is_active',
        'group_id',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'is_active' => 'boolean',
    ];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany<\App\Models\FormField, $this>
     */
    public function fields(): HasMany
    {
        return $this->hasMany(FormField::class)->orderBy('sort_order');
    }

    /**
     * Get the submissions for this form definition.
     * @return \Illuminate\Database\Eloquent\Relations\HasMany<\App\Models\FormSubmission, $this>
     */
    public function submissions(): HasMany
    {
        return $this->hasMany(FormSubmission::class);
    }

    #[\Override]
    public function delete(): bool
    {
        return DB::transaction(function () {
            foreach ($this->fields as $field) {
                $field->options()->delete();
                $field->delete();
            }

            $this->fields()->delete();

            // TODO handle submissions
            return parent::delete();
        });
    }

    public function getValidationRules(): array
    {
        $this->loadMissing(['fields', 'fields.options']);

        return $this->fields->mapWithKeys(fn (FormField $field) => [$field->uuid => $field->getValidationRules()])->toArray();
    }

    public function getValidationAttributes(): array
    {
        return $this->fields->mapWithKeys(fn (FormField $field) => [$field->id => $field->label])->toArray();
    }

    public function createSubmission(): FormSubmission
    {
        return $this->submissions()->create([
            'form_name' => $this->name,
            'form_description' => $this->description,
            'submitted_at' => now(),
            'group_id' => $this->group_id,
        ]);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasOne<\App\Models\FormDefinitionToAdvice, $this>
     */
    public function adviceCreator(): HasOne
    {
        return $this->hasOne(FormDefinitionToAdvice::class);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasOne<\App\Models\FormDefinitionToMapPoint, $this>
     */
    public function mapPointCreator(): HasOne
    {
        return $this->hasOne(FormDefinitionToMapPoint::class);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo<\App\Models\Group, $this>
     */
    public function group(): BelongsTo
    {
        return $this->belongsTo(Group::class);
    }
}
