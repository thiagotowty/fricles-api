<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class FacebookController extends Controller
{
    public function webhook(Request $request)
    {
        $verify_token = 'abcdefg12345678';

        $hub_challenge  = $request->hub_challenge;
        $hub_verify_token  = $request->hub_verify_token;

        if ($verify_token == $hub_verify_token)
            return response($hub_challenge, 200);

        return response(null, 403);

    }
}
