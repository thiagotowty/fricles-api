<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class FacebookController extends Controller
{
    public function webhook(Request $request)
    {
        dd($request);
        $verify_token = 'abcdefg12345678';

//        $hub_mode = $request->a

    }
}
