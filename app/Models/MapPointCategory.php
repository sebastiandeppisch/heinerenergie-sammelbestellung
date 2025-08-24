<?php

namespace App\Models;

use App\Models\Traits\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;
use Override;

class MapPointCategory extends Model
{
    use HasFactory;
    use HasUuid;

    protected $fillable = [
        'name',
        'image_path',
    ];

    /**
     * @return HasMany<MapPoint, $this>
     */
    public function mapPoints(): HasMany
    {
        return $this->hasMany(MapPoint::class, 'category_id');
    }

    #[Override]
    public function delete(): ?bool
    {
        if ($this->image_path) {
            Storage::disk('public')->delete($this->image_path);
        }

        foreach ($this->mapPoints as $mapPoint) {
            $mapPoint->update(['category_id' => null]);
        }

        return parent::delete();
    }
}
