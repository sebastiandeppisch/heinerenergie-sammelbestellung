<?php

namespace App\Models;

use Illuminate\Support\Carbon;
use App\Contracts\Pointable;
use App\Enums\AdviceStatusResult;
use App\Enums\AdviceType;
use App\Enums\HouseType;
use App\Events\AdviceCreated;
use App\Events\AdviceSaving;
use App\Events\AdviceUpdated;
use App\Models\Traits\HasUuid;
use App\Traits\HasPoints;
use App\ValueObjects\Address;
use App\ValueObjects\Coordinate;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphPivot;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Notifications\Notifiable;
use Wnx\Sends\Contracts\HasSends;
use Wnx\Sends\Support\HasSendsTrait;

/**
 * @property ?Coordinate $coordinate
 * @property ?Address $address
 * @property AdviceType $type
 * @property HouseType|null $house_type
 * @property Carbon $created_at
 * @property Carbon $updated_at
 */
class Advice extends Model implements HasSends, Pointable
{
    protected $table = 'advices';

    use HasFactory;
    use HasPoints;
    use HasSendsTrait;
    use HasUuid;
    use Notifiable;
    use SoftDeletes;

    protected $fillable = [
        'first_name',
        'last_name',
        'street',
        'street_number',
        'zip',
        'city',
        'email',
        'phone',
        'commentary',
        'advisor_id',
        'advice_status_id',
        'lng',
        'lat',
        'type',
        'help_type_place',
        'help_type_technical',
        'help_type_bureaucracy',
        'helpType_other',
        'house_type',
        'landlord_exists',
        'group_id',
        'place_notes',
        'address',
    ];

    protected $appends = ['shares_ids'];

    protected $dispatchesEvents = [
        'created' => AdviceCreated::class,
        'updated' => AdviceUpdated::class,
        'saving' => AdviceSaving::class,
    ];

    /**
     * @return BelongsTo<User, $this>
     */
    public function advisor(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return BelongsTo<AdviceStatus, $this>
     */
    public function status(): BelongsTo
    {
        return $this->belongsTo(AdviceStatus::class, 'advice_status_id');
    }

    /**
     * @return MorphToMany<User, $this, MorphPivot>
     */
    public function shares(): MorphToMany
    {
        return $this->morphToMany(User::class, 'sharing', 'sharings', 'sharing_id', 'advisor_id');
    }

    public function getSharesIdsAttribute(): array
    {
        return $this->shares->pluck('id')->toArray();
    }

    public function isHome(): bool
    {
        return $this->type === AdviceType::Home;
    }

    public function isDirectOrder(): bool
    {
        return $this->type === AdviceType::DirectOrder;
    }

    public function isVirtual(): bool
    {
        return $this->type === AdviceType::Virtual;
    }

    public function getResultAttribute(): AdviceStatusResult
    {
        return $this->status->result ?? AdviceStatusResult::New;
    }

    public function getNameAttribute(): string
    {
        return sprintf('%s %s', $this->first_name, $this->last_name);
    }

    /**
     * @return BelongsTo<Group, $this>
     */
    public function group(): BelongsTo
    {
        return $this->belongsTo(Group::class);
    }

    /**
     * @return HasMany<AdviceEvent, $this>
     */
    public function events(): HasMany
    {
        return $this->hasMany(AdviceEvent::class);
    }

    protected function casts(): array
    {
        return [
            'first_name' => 'string',
            'last_name' => 'string',
            'street' => 'string',
            'street_number' => 'string',
            'zip' => 'string',
            'city' => 'string',
            'email' => 'string',
            'phone' => 'string',
            'commentary' => 'string',
            'advice_status_id' => 'int',
            'type' => AdviceType::class,
            'help_type_place' => 'boolean',
            'help_type_technical' => 'boolean',
            'help_type_bureaucracy' => 'boolean',
            'helpType_other' => 'boolean',
            'house_type' => HouseType::class,
            'landlord_exists' => 'boolean',
            'place_notes' => 'string',
            'address' => Address::class,
            'coordinate' => Coordinate::class,
            'lng' => 'float',
            'lat' => 'float',
            'advisor_id' => 'int',
        ];
    }
}
