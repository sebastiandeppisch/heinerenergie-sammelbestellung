<?php

namespace App\Http\Controllers;

use App\Actions\FetchCoordinateByFreeText;
use Illuminate\Http\Request;

class GeoSearchController extends Controller
{
    public function __invoke(Request $request)
    {
        $text = $request->input('query');
        $coordinate = app(FetchCoordinateByFreeText::class)($text);

        return response()->json($coordinate);
    }
}
