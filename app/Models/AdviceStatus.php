<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class AdviceStatus extends Model
{
    use HasFactory;

    protected $table = 'advice_status';

    protected $fillable = ['name'];

    public function adivces(): BelongsTo
    {
        return $this->belongsTo(Advice::class);
    }
}
