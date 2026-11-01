<?php

declare(strict_types=1);

namespace App\HexNet\Models;

use App\HexNet\Enums\HexNetStatus;
use App\HexNet\Services\HexGrid;
use App\Models\FormDefinition;
use App\Models\Group;
use App\Models\MapPointCategory;
use App\Models\Traits\HasUuid;
use App\ValueObjects\Coordinate;
use App\ValueObjects\Polygon;
use Database\Factories\HexNetFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Carbon;

/**
 * A HexaNetz campaign: map points of its categories fill a hexagon grid over the play area.
 *
 * @property int $id
 * @property string $uuid
 * @property int $group_id
 * @property string $name
 * @property string $slug
 * @property HexNetStatus $status
 * @property string|null $title
 * @property string|null $subtitle
 * @property string|null $cta_text
 * @property Polygon|null $play_area
 * @property float|null $grid_origin_lat
 * @property float|null $grid_origin_lng
 * @property int $hex_radius_m
 * @property bool $grid_locked
 * @property int $threshold_medium
 * @property int $threshold_dark
 * @property int $jitter_m
 * @property int|null $form_definition_id
 * @property Carbon $created_at
 * @property Carbon $updated_at
 */
class HexNet extends Model
{
    /** @use HasFactory<HexNetFactory> */
    use HasFactory;

    use HasUuid;

    public const int MIN_RADIUS_M = 100;

    public const int MAX_RADIUS_M = 1000;

    protected $fillable = [
        'group_id',
        'name',
        'slug',
        'status',
        'title',
        'subtitle',
        'cta_text',
        'play_area',
        'grid_origin_lat',
        'grid_origin_lng',
        'hex_radius_m',
        'grid_locked',
        'threshold_medium',
        'threshold_dark',
        'jitter_m',
        'form_definition_id',
    ];

    protected $attributes = [
        'status' => 'draft',
        'hex_radius_m' => 150,
        'grid_locked' => false,
        'threshold_medium' => 2,
        'threshold_dark' => 3,
        'jitter_m' => 40,
    ];

    protected function casts(): array
    {
        return [
            'group_id' => 'integer',
            'form_definition_id' => 'integer',
            'status' => HexNetStatus::class,
            'play_area' => Polygon::class,
            'grid_origin_lat' => 'float',
            'grid_origin_lng' => 'float',
            'hex_radius_m' => 'integer',
            'grid_locked' => 'boolean',
            'threshold_medium' => 'integer',
            'threshold_dark' => 'integer',
            'jitter_m' => 'integer',
        ];
    }

    protected static function newFactory(): HexNetFactory
    {
        return HexNetFactory::new();
    }

    /**
     * @return BelongsTo<Group, $this>
     */
    public function group(): BelongsTo
    {
        return $this->belongsTo(Group::class);
    }

    /**
     * @return BelongsToMany<MapPointCategory, $this>
     */
    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(MapPointCategory::class, 'hex_net_map_point_category');
    }

    /**
     * @return BelongsTo<FormDefinition, $this>
     */
    public function formDefinition(): BelongsTo
    {
        return $this->belongsTo(FormDefinition::class);
    }

    public function isActive(): bool
    {
        return $this->status === HexNetStatus::ACTIVE;
    }

    public function hasPlayArea(): bool
    {
        return $this->play_area !== null && count($this->play_area->coordinates) >= 3;
    }

    public function origin(): ?Coordinate
    {
        if ($this->grid_origin_lat === null || $this->grid_origin_lng === null) {
            return null;
        }

        return new Coordinate($this->grid_origin_lat, $this->grid_origin_lng);
    }

    public function grid(): ?HexGrid
    {
        $origin = $this->origin();

        return $origin === null ? null : new HexGrid($origin, $this->hex_radius_m);
    }

    /**
     * 0 open, 1 light, 2 medium, 3 dark.
     */
    public function levelFor(int $count): int
    {
        return match (true) {
            $count >= $this->threshold_dark => 3,
            $count >= $this->threshold_medium => 2,
            $count >= 1 => 1,
            default => 0,
        };
    }
}
