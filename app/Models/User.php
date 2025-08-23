<?php

namespace App\Models;

use App\Models\Traits\HasUuid;
use App\Traits\HasGroups;
use App\ValueObjects\Address;
use App\ValueObjects\Coordinate;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

/**
 * @property Coordinate|null $coordinate
 * @property Address|null $address
 */
class User extends Authenticatable
{
    use HasApiTokens, HasFactory, HasGroups, HasUuid, Notifiable;

    protected $fillable = [
        'first_name',
        'last_name',
        'email',
        'password',
        'is_admin',
        'street',
        'street_number',
        'zip',
        'city',
        'advice_radius',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $appends = [
        'name',
    ];

    /**
     * @return HasMany<Advice, $this>
     */
    public function advices(): HasMany
    {
        return $this->hasMany(Advice::class);
    }

    public function getNameAttribute()
    {
        return sprintf('%s %s', $this->first_name, $this->last_name);
    }

    public function shouldBeNotifiedForNearbyAdvice(Advice $advice): bool
    {
        if ($this->coordinate === null || $this->advice_radius === null || $advice->coordinate === null) {
            return false;
        }

        return $this->coordinate->distanceTo($advice->coordinate)->getValue() <= $this->advice_radius;
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
            'advice_radius' => 'int',
        ];
    }

    public static function empty(): bool
    {
        return self::count() === 0;
    }
}
