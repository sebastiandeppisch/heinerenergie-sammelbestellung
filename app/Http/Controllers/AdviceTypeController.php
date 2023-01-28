<?php

namespace App\Http\Controllers;

use App\AdviceType;

class AdviceTypeController extends Controller
{
    public function index()
    {
       // dd(AdviceType::cases());
        return collect(AdviceType::cases())->map(fn($item, $key) => [
            'id' => $key,
            'name' => $item->name
        ]);
    }


    public function show(int $advicestatus){
        return [
            'id' => $advicestatus,
            'name' => AdviceType::cases()[$advicestatus]->name
        ];
    }

}
