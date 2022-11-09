<?php

namespace App\Models;

use App\Events\AdviceCreated;
use App\Events\AdviceUpdated;
use Illuminate\Support\Facades\Auth;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Advice extends Model
{
    protected $table = 'advices';

    use HasFactory;

    protected $fillable = ['firstName', 'lastName', 'street', 'streetNumber', 'zip', 'city', 'email', 'phone', 'commentary', 'advisor_id'];

    protected $appends = ['distance'];

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
        'lat' => 'float'
    ];

    public function advisor(): BelongsTo{
        return $this->belongsTo(User::class);
    }

    public function getDistanceAttribute(): ?float{
        $user = Auth::user();
        if($this->lat === null || $this->long === null || $user->long === null || $user->lat === null){
            return null;
        }
        return $this->haversineGreatCircleDistance($this->lat, $this->long, $user->lat, $user->long);
    }

    private function haversineGreatCircleDistance(
        $latitudeFrom, $longitudeFrom, $latitudeTo, $longitudeTo, $earthRadius = 6371000): float
      {
        // convert from degrees to radians
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
}
