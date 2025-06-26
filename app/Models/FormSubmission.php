<?php

namespace App\Models;

use App\Contracts\Pointable;
use App\Traits\HasPoints;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FormSubmission extends Model implements Pointable
{
    use HasFactory;
    use HasUuids;
    use HasPoints;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string, mixed>
     */
    protected $fillable = [
        'form_definition_id',
        'advice_id',
        'form_name',
        'form_description',
        'submitted_at',
    ];

    public function casts(){
        return [
            'submitted_at' => 'datetime',
        ];
    }

    public function formDefinition(): BelongsTo
    {
        return $this->belongsTo(FormDefinition::class);
    }

    public function submissionFields(): HasMany
    {
        return $this->hasMany(SubmissionField::class)->orderBy('sort_order');
    }

}
