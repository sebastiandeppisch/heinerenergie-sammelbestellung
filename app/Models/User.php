<?php

namespace App\Models;

use App\Traits\HasGroups;
use App\ValueObjects\Address;
use App\ValueObjects\Coordinate;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Auth;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, HasGroups, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'first_name',
        'last_name',
        'email',
        'password',
        'is_admin',
        'street',
        'streetNumber',
        'zip',
        'city',
        'advice_radius',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $appends = [
        'name',
        'isActingAsAdmin',
    ];

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class, 'advisor_id');
    }

    public function advices(): HasMany
    {
        return $this->hasMany(Advice::class);
    }

    public function getNameAttribute()
    {
        return sprintf('%s %s', $this->first_name, $this->last_name);
    }

    public function sharedOrders(): MorphMany
    {
        return $this->morphMany(Order::class, 'sharings');
    }

    public function getIsActingAsAdminAttribute(): bool
    {
        return $this->isActingAsAdmin();
    }

    public function isActingAsAdmin(): bool
    {
        return $this->is_admin && Auth::user()?->id === $this->id && session()->get('isAdmin') === true;
    }

    public function shouldBeNotifiedForNearbyAdvice(Advice $advice): bool
    {
        if ($this->coordinate === null || $this->advice_radius === null || $advice->coordinate === null) {
            return false;
        }

        return $this->coordinate->distanceTo($advice->coordinate) <= $this->advice_radius;
    }

    /**
     * The attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'is_admin' => 'bool',
            'address' => Address::class,
            'coordinate' => Coordinate::class,
            'long' => 'float',
            'lat' => 'float',
            'advice_radius' => 'int',
        ];
    }
}
