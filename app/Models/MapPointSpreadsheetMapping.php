<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\MapPointSpreadsheetField;
use App\Models\Traits\HasUuid;
use Database\Factories\MapPointSpreadsheetMappingFactory;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A saved assignment of spreadsheet columns to map point fields, shared within a group.
 * The same mapping drives the import and the export, so exported files can be imported again.
 *
 * @property int $group_id
 * @property string $name
 * @property array<int, array{header: string, field: string}> $columns
 * @property MapPointSpreadsheetField $key_field
 */
class MapPointSpreadsheetMapping extends Model
{
    /** @use HasFactory<MapPointSpreadsheetMappingFactory> */
    use HasFactory;

    use HasUuid;

    protected $fillable = [
        'group_id',
        'name',
        'columns',
        'key_field',
    ];

    protected $casts = [
        'columns' => 'array',
        'key_field' => MapPointSpreadsheetField::class,
    ];

    /**
     * @return BelongsTo<Group, $this>
     */
    public function group(): BelongsTo
    {
        return $this->belongsTo(Group::class);
    }

    /**
     * @param  Builder<MapPointSpreadsheetMapping>  $query
     */
    #[Scope]
    protected function ownedByGroup(Builder $query, Group $group): void
    {
        $query->where('group_id', $group->id);
    }
}
