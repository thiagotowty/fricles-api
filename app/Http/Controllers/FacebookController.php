<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class FacebookController extends Controller
{
    public function webhookGET(Request $request)
    {
        Log::info('get');
//        $verify_token = 'abcdefg12345678';
//
        $hub_challenge = $request->hub_challenge;
//        $hub_verify_token  = $request->hub_verify_token;
//
//        if ($verify_token == $hub_verify_token)
        return response($hub_challenge, 200);
//
//        return response(null, 403);
    }

    public function webhookPOST(Request $request)
    {
        Log::info('post');

        Log::warning('request' . $request->message["text"]);

        return response(null, 200);


//        $return = new class {};
//
//        $receipt = new class {};
//        $receipt->id = '';
//
//        $message = new class {};
//        $message->text = 'oi';
    }
}
