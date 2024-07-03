<?php

namespace App\Models;

use App\Enums\AdviceStatusResult;
use App\Enums\AdviceType;
use App\Enums\HouseType;
use App\Events\AdviceCreated;
use App\Events\AdviceUpdated;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Auth;

class Advice extends Model
{
    protected $table = 'advices';

    use HasFactory;
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
        if ($this->lat === null || $this->long === null || $user->long === null || $user->lat === null) {
            return null;
        }

        return $this->haversineGreatCircleDistance($this->lat, $this->long, $user->lat, $user->long);
    }

    private function haversineGreatCircleDistance(
        $latitudeFrom, $longitudeFrom, $latitudeTo, $longitudeTo, $earthRadius = 6371000): float
    {
        $latFrom = deg2rad($latitudeFrom);
        $lonFrom = deg2rad($longitudeFrom);
        $latTo = deg2rad($latitudeTo);
        $lonTo = deg2rad($longitudeTo);

        $latDelta = $latTo - $latFrom;
        $lonDelta = $lonTo - $lonFrom;

        $angle = 2 * asin(sqrt(sin($latDelta / 2) ** 2 +
          cos($latFrom) * cos($latTo) * sin($lonDelta / 2) ** 2));

        return $angle * $earthRadius;
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
        return $this->status?->result;
    }
}