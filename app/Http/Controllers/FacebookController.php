<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class FacebookController extends Controller
{
    public function webhook(Request $request)
    {
//        $verify_token = 'abcdefg12345678';
//
        $hub_challenge  = $request->hub_challenge;
//        $hub_verify_token  = $request->hub_verify_token;
//
//        if ($verify_token == $hub_verify_token)
            return response($hub_challenge, 200);
//
//        return response(null, 403);

        Log::info('entrou');

        $sender_id = $request->sender->id;
        $recipient_id = $request->recipient->id;

        Log::info('$sender_id ' . $sender_id);
        Log::info('$recipient_id ' . $recipient_id);

        $retorno = new class{};
        $retorno->sender = new class {};
        $retorno->sender->id = $sender_id;

        $retorno->recipient = new class {};
        $retorno->recipient->id = $recipient_id;

        $retorno->message = new class{};
        if ($request->message->text == 1) {
            $retorno->message->text = "deu certo";
        }

        $retorno->message->text = "não deu certo";

        return response()->json($retorno, 200);
    }
}
