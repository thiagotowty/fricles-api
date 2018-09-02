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
        $sender_id = $request->entry[0]["messaging"][0]["sender"]["id"];
        if ($sender_id == "196748573818793")
            return response()->json("EVENT_RECEIVED", 200);

        Log::warning('REQUEST ' . $request);

        $object = new class{};
        $object->messaging_type = "RESPONSE";
        $recipient = new class{};
        $recipient->id = $sender_id;
        $message = new class{};
        $message->text = "Teste legal";

        $object->recipient = $recipient;
        $object->message = $message;


        $access_token = "EAAFdwO6fUOcBAJZCgCH2IHTuSezOyl2oK18Fyqs8LY5ZBEB8iZA7mtWBnl5xvSzeiWazemBXn1ZB4pqNZAyUVyHuUPu1oet4pk5ihehZBAZAZAAIVZAQFe4r2jlLngIzo062bCZAc2pMLvwmCc2a0hZCV24uwuEJbr2SZAIZC1CRXG18kelDmkpEN4k8H";
        $curl = curl_init("https://graph.facebook.com/v2.6/me/messages?access_token=".$access_token);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($object));
        curl_setopt($curl, CURLOPT_HTTPHEADER, array(
                'Content-Type: application/json'
        ));
//        curl_exec($curl);
        $retorno = curl_exec($curl);
        $info = curl_getinfo($curl);
        curl_close($curl);

        Log::info("======================================================================");
        Log::warning("STATUS_CODE " . $info['http_code']);
        Log::warning("CURL " . $retorno);

        return response()->json("EVENT_RECEIVED", 200);
    }
}
