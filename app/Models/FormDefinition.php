<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Facades\DB;

class FormDefinition extends Model
{
    use HasFactory;
    use HasUuids;

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
     * @return HasMany<FormField>
     */
    public function fields(): HasMany
    {
        return $this->hasMany(FormField::class)->orderBy('sort_order');
    }

    /**
     * Get the submissions for this form definition.
     */
    public function submissions(): HasMany
    {
        return $this->hasMany(FormSubmission::class);
    }

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

        return $this->fields->mapWithKeys(function (FormField $field) {
            return [$field->id => $field->getValidationRules()];
        })->toArray();
    }

    public function getValidationAttributes(): array
    {
        return $this->fields->mapWithKeys(function (FormField $field) {
            return [$field->id => $field->label];
        })->toArray();
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
     * @return HasOne<FormDefinitionToAdvice>
     */
    public function adviceCreator(): HasOne
    {
        return $this->hasOne(FormDefinitionToAdvice::class);
    }
}
