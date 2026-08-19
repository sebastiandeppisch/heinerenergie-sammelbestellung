<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Enums\AdviceType;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

class AdviceTypeController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json(
            collect(AdviceType::cases())->map(fn (AdviceType $item, int $key): array => [
                'id' => $key,
                'name' => $item->name,
            ])
        );
    }

    /**
     * @return array<string, int|string>
     */
    public function show(int $advicestatus): array
    {
        return [
            'id' => $advicestatus,
            'name' => AdviceType::cases()[$advicestatus]->name,
        ];
    }
}
