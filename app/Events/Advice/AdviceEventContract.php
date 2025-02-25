<?php

namespace App\Events\Advice;

interface AdviceEventContract
{
    public function getDescription(): string;
}
