<?php

declare(strict_types=1);

namespace App\Events\Advice;

use App\Models\Advice;
use App\Models\User;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

abstract class AdviceEvent implements AdviceEventContract
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

    public function __construct(
        public Advice $advice,
        public ?User $user = null
    ) {}

    abstract public function getDescription(): string;
}
