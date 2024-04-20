<?php

namespace App\Models;

use App\Enums\AdviceStatusResult;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class AdviceStatus extends Model
{
    use HasFactory;

    protected $table = 'advice_status';

    protected $fillable = ['name', 'result'];

    protected $casts = [
        'name' => 'string',
        'result' => AdviceStatusResult::class,
    ];

    public function advices(): BelongsTo
    {
        return $this->belongsTo(Advice::class);
    }
}
