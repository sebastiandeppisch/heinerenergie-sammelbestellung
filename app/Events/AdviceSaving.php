<?php

namespace App\Events;

use App\Models\Advice;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class AdviceSaving
{
    use Dispatchable, SerializesModels;

    public function __construct(public Advice $advice) {}
}
