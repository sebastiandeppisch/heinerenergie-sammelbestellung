<?php

namespace App\Models;

use App\Enums\AdviceStatusResult;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AdviceStatus extends Model
{
    use HasFactory;

    protected $table = 'advice_status';

    protected $fillable = ['name', 'result'];

    public function advices(): BelongsTo
    {
        return $this->belongsTo(Advice::class);
    }

    protected function casts(): array
    {
        return [
            'name' => 'string',
            'result' => AdviceStatusResult::class,
        ];
    }
}
