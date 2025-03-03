<?php

namespace App\Models;

use App\Enums\AdviceStatusResult;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class AdviceStatus extends Model
{
    use HasFactory;

    protected $table = 'advice_status';

    protected $fillable = ['name', 'result', 'group_id'];

    public function advices(): BelongsTo
    {
        return $this->belongsTo(Advice::class);
    }

    public function ownerGroup(): BelongsTo
    {
        return $this->belongsTo(Group::class, 'group_id');
    }

    public function usingGroups(): BelongsToMany
    {
        return $this->belongsToMany(Group::class)
            ->withPivot('visible_in_group')
            ->withTimestamps()
            ->using(AdviceStatusGroup::class);
    }

    protected function casts(): array
    {
        return [
            'name' => 'string',
            'result' => AdviceStatusResult::class,
        ];
    }

    public function isVisibleInGroup(Group $group): bool
    {
        if ($this->group_id === null || $this->group_id === $group->id) {
            return true;
        }

        $pivot = $this->usingGroups()->where('groups.id', $group->id)->first()?->pivot;

        return $pivot?->visible_in_group ?? true;
    }
}
