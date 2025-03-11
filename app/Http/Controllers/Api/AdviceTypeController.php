<?php

namespace App\Http\Controllers\Api;

use App\Enums\AdviceType;
use App\Http\Controllers\Controller;

class AdviceTypeController extends Controller
{
    public function index()
    {
        return collect(AdviceType::cases())->map(fn ($item, $key) => [
            'id' => $key,
            'name' => $item->name,
        ]);
    }

    public function show(int $advicestatus)
    {
        return [
            'id' => $advicestatus,
            'name' => AdviceType::cases()[$advicestatus]->name,
        ];
    }
}
