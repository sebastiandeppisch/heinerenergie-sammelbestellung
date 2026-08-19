<?php

declare(strict_types=1);

namespace App\Events\Advice;

interface AdviceEventContract
{
    public function getDescription(): string;
}
