<?php

namespace App\Models;

use App\Enums\HouseType;
use App\Enums\AdviceType;
use App\Events\AdviceCreated;
use App\Events\AdviceUpdated;
use App\ValueObjects\Address;
use App\ValueObjects\Coordinate;
use App\Enums\AdviceStatusResult;
use Wnx\Sends\Contracts\HasSends;
use Illuminate\Support\Facades\Auth;
use Wnx\Sends\Support\HasSendsTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\MorphToMany;

class Advice extends Model implements HasSends
{
    protected $table = 'advices';

    use HasFactory;
    use SoftDeletes;
    use HasSendsTrait;

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
        'long',
        'lat',
        'type',
        'helpType_place',
        'helpType_technical',
        'helpType_bureaucracy',
        'helpType_other',
        'houseType',
        'landlordExists',
        'placeNotes',
    ];

    protected $appends = ['distance', 'shares_ids'];

    protected $dispatchesEvents = [
        'created' => AdviceCreated::class,
        'updated' => AdviceUpdated::class,
    ];

    protected $casts = [
        'firstName' => 'string',
        'lastName' => 'string',
        'street' => 'string',
        'streetNumber' => 'string',
        'zip' => 'integer',
        'city' => 'string',
        'email' => 'string',
        'phone' => 'string',
        'commentary' => 'string',
        'advisor_id' => 'integer',
        'long' => 'float',
        'lat' => 'float',
        'advice_status_id' => 'integer',
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
        'long' => 'float',
        'lat' => 'float',
    ];

    public function advisor(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function getDistanceAttribute(): ?float
    {
        if (Auth::user() === null) {
            return null;
        }

        return $this->getDistanceToUser(Auth::user());
    }

    public function getDistanceToUser(User $user): ?float
    {
        if($this->coordinate === null || $user->coordinate === null) {
            return null;
        }
        return $this->coordinate->distanceTo($user->coordinate);
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
}