<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class UploadController extends Controller
{
    public function __invoke(Request $request){
        $file = $request->file('file');
        $path = $request->path . '/' . $file->getClientOriginalName();
        $path = str_replace('//', '/', $path);


        $name = $path;
        $i = 1;
        while(Storage::disk('uploads')->exists($name)){
            $name = $request->path.'/'.pathinfo($path, PATHINFO_FILENAME) . '(' . $i . ').' . pathinfo($path, PATHINFO_EXTENSION);
            $i++;
        }
        $path = $request->file('file')->storeAs('', $name, 'uploads');
        $path = str_replace('//', '/', $path);
        return [
            'url' => url('uploads/'.$path)
        ];
    }
}
