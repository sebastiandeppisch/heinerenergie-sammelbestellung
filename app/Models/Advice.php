<?php

namespace App\Models;

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
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Notifications\Notifiable;
use Wnx\Sends\Contracts\HasSends;
use Wnx\Sends\Support\HasSendsTrait;

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
        'firstName',
        'lastName',
        'street',
        'streetNumber',
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
        'helpType_place',
        'helpType_technical',
        'helpType_bureaucracy',
        'helpType_other',
        'houseType',
        'landlordExists',
        'group_id',
        'placeNotes',
        'address',
    ];

    protected $appends = ['shares_ids'];

    protected $dispatchesEvents = [
        'created' => AdviceCreated::class,
        'updated' => AdviceUpdated::class,
        'saving' => AdviceSaving::class,
    ];

    public function advisor(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function status(): BelongsTo
    {
        return $this->belongsTo(AdviceStatus::class, 'advice_status_id');
    }

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
        return $this->status?->result ?? AdviceStatusResult::New;
    }

    public function getNameAttribute(): string
    {
        return sprintf('%s %s', $this->firstName, $this->lastName);
    }

    public function group(): BelongsTo
    {
        return $this->belongsTo(Group::class);
    }

    public function events(): HasMany
    {
        return $this->hasMany(AdviceEvent::class);
    }

    protected function casts(): array
    {
        return [
            'firstName' => 'string',
            'lastName' => 'string',
            'street' => 'string',
            'streetNumber' => 'string',
            'zip' => 'integer',
            'city' => 'string',
            'email' => 'string',
            'phone' => 'string',
            'commentary' => 'string',
            'advice_status_id' => 'string',
            'type' => AdviceType::class,
            'helpType_place' => 'boolean',
            'helpType_technical' => 'boolean',
            'helpType_bureaucracy' => 'boolean',
            'helpType_other' => 'boolean',
            'houseType' => HouseType::class,
            'landlordExists' => 'boolean',
            'placeNotes' => 'string',
            'address' => Address::class,
            'coordinate' => Coordinate::class,
            'lng' => 'float',
            'lat' => 'float',
            'advisor_id' => 'int',
        ];
    }
}
