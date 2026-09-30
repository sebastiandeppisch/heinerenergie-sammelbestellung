<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\FieldType;
use App\Models\Traits\HasUuid;
use Database\Factories\MapPointFieldFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;
use Override;

/**
 * The value of a category field on a point, together with a snapshot of the field as it was
 * when the value was entered. The field itself may be renamed or deleted later.
 *
 * @property int|null $form_field_id
 * @property FieldType $type
 * @property mixed $value
 */
class MapPointField extends Model
{
    /** @use HasFactory<MapPointFieldFactory> */
    use HasFactory;

    use HasUuid;

    protected $fillable = [
        'map_point_id',
        'form_field_id',
        'value',
        'type',
        'label',
        'sort_order',
    ];

    protected $casts = [
        'sort_order' => 'integer',
        'type' => FieldType::class,
        'value' => 'json',
    ];

    /**
     * @return BelongsTo<MapPoint, $this>
     */
    public function mapPoint(): BelongsTo
    {
        return $this->belongsTo(MapPoint::class);
    }

    /**
     * @return BelongsTo<FormField, $this>
     */
    public function formField(): BelongsTo
    {
        return $this->belongsTo(FormField::class);
    }

    /**
     * @return HasMany<MapPointFieldOption, $this>
     */
    public function options(): HasMany
    {
        return $this->hasMany(MapPointFieldOption::class)->orderBy('sort_order');
    }

    /**
     * The option snapshots belong to this value only. The foreign key does not cascade, so they are deleted here.
     */
    #[Override]
    public function delete(): ?bool
    {
        return DB::transaction(function (): ?bool {
            $this->options()->delete();

            return parent::delete();
        });
    }
}
