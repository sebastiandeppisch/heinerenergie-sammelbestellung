<?php

namespace App\Models;

use App\Models\Traits\HasUuid;
use App\ValueObjects\Polygon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\Pivot;
use Illuminate\Support\Facades\Storage;
use Override;

class Group extends Model
{
    use HasFactory;
    use HasUuid;

    protected $fillable = [
        'name',
        'description',
        'dashboard_info',
        'new_advice_mail',
        'logo_path',
        'marker_path',
        'parent_id',
        'accepts_transfers',
    ];

    protected $casts = [
        'consulting_area' => Polygon::class,
        'accepts_transfers' => 'boolean',
    ];

    /**
     * Get the users that belong to this group
     *
     * @return BelongsToMany<User, $this, Pivot>
     */
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class)
            ->withPivot('is_admin')
            ->withTimestamps();
    }

    /**
     * Get the admins of this group
     *
     * @return BelongsToMany<User, $this, Pivot>
     */
    public function admins(): BelongsToMany
    {
        return $this->belongsToMany(User::class)
            ->wherePivot('is_admin', true)
            ->withTimestamps();
    }

    /**
     * Get the parent group
     *
     * @return BelongsTo<\App\Models\Group, $this>
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(Group::class, 'parent_id');
    }

    /**
     * Get the child groups
     *
     * @return HasMany<\App\Models\Group, $this>
     */
    public function children(): HasMany
    {
        return $this->hasMany(Group::class, 'parent_id');
    }

    /**
     * Get all advices belonging to this group
     *
     * @return HasMany<Advice, $this>
     */
    public function advices(): HasMany
    {
        return $this->hasMany(Advice::class);
    }

    /**
     * Check if this group is a main group (no parent)
     */
    public function isMainGroup(): bool
    {
        return $this->parent_id === null;
    }

    /**
     * Get all ancestor groups
     *
     * @return Collection<Group>
     */
    public function ancestors(): Collection
    {
        $ancestors = new Collection;
        $this->loadMissing('parent');
        $current = $this->parent;

        while ($current !== null) {
            $current->loadMissing('parent');
            $ancestors->push($current);
            $current = $current->parent;
        }

        return $ancestors;
    }

    /**
     * Get all descendant groups
     *
     * @return Collection<Group>
     */
    public function descendants(): Collection
    {
        $descendants = new Collection;
        $queue = $this->children()->get();

        while ($queue->isNotEmpty()) {
            $current = $queue->shift();
            $descendants->push($current);
            $queue = $queue->merge($current->children()->get());
        }

        return $descendants;
    }

    public function getFullLogoPathAttribute()
    {
        if (str_starts_with($this->logo_path, 'http')) {
            return $this->logo_path;
        }

        return $this->logo_path ? Storage::url($this->logo_path) : null;
    }

    public function getFullMarkerPathAttribute()
    {
        if ($this->marker_path && str_starts_with($this->marker_path, 'http')) {
            return $this->marker_path;
        }

        return $this->marker_path ? Storage::url($this->marker_path) : null;
    }

    #[Override]
    public function delete(): ?bool
    {
        if ($this->logo_path) {
            Storage::disk('public')->delete($this->logo_path);
        }

        if ($this->marker_path) {
            Storage::disk('public')->delete($this->marker_path);
        }

        return parent::delete();
    }

    protected function casts(): array
    {
        return [
            'accepts_transfers' => 'boolean',
        ];
    }

    public function getHierarchyIds(): array
    {
        $ids = [$this->id];
        $current = $this;

        while ($current->parent_id) {
            $current = $current->parent;
            $ids[] = $current->id;
        }

        return $ids;
    }

    /**
     * @return BelongsToMany<AdviceStatus, $this, AdviceStatusGroup>
     */
    public function usableStatuses(): BelongsToMany
    {
        return $this->belongsToMany(AdviceStatus::class)->withPivot('visible_in_group')->using(AdviceStatusGroup::class);
    }

    /**
     * @return HasMany<AdviceStatus, $this>
     */
    public function ownStatuses(): HasMany
    {
        return $this->hasMany(AdviceStatus::class);
    }

    public function isLeaf(): bool
    {
        return $this->children()->count() === 0;
    }
}
