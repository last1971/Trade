<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class TestController extends Controller
{
    //
    public function test(Request $request)
    {
        $a = $request->input();
        $b = $request->query();
        $c = collect($request->route()->parameters)->get('test2');
        dd($request);
    }
}
