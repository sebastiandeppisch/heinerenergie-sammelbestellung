<?php

namespace App\Models;

use App\Enums\AdviceStatusResult;
use App\Models\Traits\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\Pivot;
use Illuminate\Database\Eloquent\SoftDeletes;

class AdviceStatus extends Model
{
    use HasFactory;
    use HasUuid;
    use SoftDeletes;

    protected $table = 'advice_status';

    protected $fillable = ['name', 'result', 'group_id'];

    /**
     * @return BelongsTo<Advice, $this>
     */
    public function advices(): BelongsTo
    {
        return $this->belongsTo(Advice::class);
    }

    /**
     * @return BelongsTo<Group, $this>
     */
    public function ownerGroup(): BelongsTo
    {
        return $this->belongsTo(Group::class, 'group_id');
    }

    /**
     * @return BelongsToMany<Group, $this, Pivot>
     */
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
        // Check if there is an explicit pivot entry for this group
        $pivot = $this->usingGroups()->where('groups.id', $group->id)->first()?->pivot;

        if ($pivot !== null) {
            // If an explicit entry exists, use its visibility value
            return $pivot->visible_in_group;
        }

        // No entry found, visible by default
        return true;
    }
}
