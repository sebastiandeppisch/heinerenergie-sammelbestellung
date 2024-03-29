<?php

namespace App\Models;

use App\AdviceType;
use App\Models\AdviceStatus;
use App\Events\AdviceCreated;
use App\Events\AdviceUpdated;
use Illuminate\Support\Facades\Auth;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\MorphToMany;

class Advice extends Model
{
    protected $table = 'advices';

    use HasFactory;

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
    ];

    public function advisor(): BelongsTo{
        return $this->belongsTo(User::class);
    }

    public function getDistanceAttribute(): ?float{
        return $this->getDistanceToUser(Auth::user());
    }

    public function getDistanceToUser(User $user): ?float{
        if($this->lat === null || $this->long === null || $user->long === null || $user->lat === null){
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
      
        $angle = 2 * asin(sqrt(pow(sin($latDelta / 2), 2) +
          cos($latFrom) * cos($latTo) * pow(sin($lonDelta / 2), 2)));
        return $angle * $earthRadius;
      }

    public function status(): HasOne{
        return $this->hasOne(AdviceStatus::class);
    }

    public function shares(): MorphToMany{
        return $this->morphToMany(User::class, 'sharing', 'sharings', 'sharing_id', 'advisor_id');
    }

    public function getSharesIdsAttribute(): array{
        return $this->shares->pluck('id')->toArray();
    }
}
