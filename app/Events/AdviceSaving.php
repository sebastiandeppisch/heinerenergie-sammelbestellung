<?php

declare(strict_types=1);

namespace App\Events;

use App\Models\Advice;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class AdviceSaving
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(public Advice $advice) {}
}
