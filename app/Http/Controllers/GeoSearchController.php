<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Actions\FetchCoordinateByFreeText;

class GeoSearchController extends Controller
{
    public function __invoke(Request $request)
    {
        $text = $request->input('query');
        $coordinate = app(FetchCoordinateByFreeText::class)($text);
        return response()->json($coordinate);
    }
}
