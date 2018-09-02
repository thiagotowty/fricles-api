<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class FacebookController extends Controller
{
    public function webhookGET(Request $request)
    {
        Log::info('get');
        $hub_challenge = $request->hub_challenge;
        return response($hub_challenge, 200);
    }

    public function webhookPOST(Request $request)
    {
        Log::info('post');
        Log::warning('REQUEST ' . $request);

        $this->sendAsyncFacebook($this->buildMessage($request));

        return response()->json("EVENT_RECEIVED", 200);
    }

    private function buildMessage(Request $request)
    {
        $sender_id = $request->entry[0]["messaging"][0]["sender"]["id"];

        $object = new class{};
        $object->messaging_type = "RESPONSE";
        $recipient = new class{};
        $recipient->id = $sender_id;
        $message = new class{};
        $message->text = "Teste legal";

        $object->recipient = $recipient;
        $object->message = $message;

        return $object;
    }

    private function sendAsyncFacebook($data)
    {
        $access_token = "EAAFdwO6fUOcBAELFNEYDNEdF7AEvQUa1YgbU2ZBoqNz3pR3zIFZAh1ynDF5txUql8cYIAVXqhJtZCkOJ5AH5q6ZCTfJrCbLTse4BUQdw7LIlyjTac0KcEiTmgXKnyl2dZATQTUlvA4tTHKVfETSuSaDZAFEM01ZCJU828ZCHXigLPAZDZD";
        $curl = curl_init("https://graph.facebook.com/v2.6/me/messages?access_token=".$access_token);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($curl, CURLOPT_HTTPHEADER, array(
            'Content-Type: application/json'
        ));
        $retorno = curl_exec($curl);
        $info = curl_getinfo($curl);
        curl_close($curl);

        Log::info("======================================================================");
        Log::warning("STATUS_CODE " . $info['http_code']);
        Log::warning("CURL " . $retorno);

    }

    public function getDebits($plate)
    {
        $url = "https://api.emplacai.towty.com.br/placa/" . $plate;
        $curl = curl_init($url);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        $retorno = json_decode(curl_exec($curl));

        $debits = $retorno->Conteudo->Debitos->DebitosDTO->Debito->DebitoDETRANNETDTO;

        $valor_total = 0;
        if(is_array($debits)) {
            foreach ($debits as $debit) {
                if($debit->Tipo == "IPVA01") continue;

                $valor_total += $debit->Valor;
            }
        } else {
            $valor_total = $debits->Valor;
        }

        return response()->json($valor_total, 200);

    }

    public function paymentAtar($value)
    {
        $object = new class{};
        $object->amount = $value;
        $curl = curl_init("https://pay-dot-wearatar-dev.appspot.com/transfer/share");
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($object));
        curl_setopt($curl, CURLOPT_HTTPHEADER, array(
            'Content-Type: application/json',
            'Authorization: Basic ZW1wbGFjYWlfdGVzdF92OGExcjZlZmxlRHJsdXN0OjQ2QTQzMTUyLTY1QkEtNEJERC04MjI3LTJGODRGQ0IzMTBEOQ=='
        ));
        $return = json_decode(curl_exec($curl));
        curl_close($curl);

        return response()->json($return , 200);
    }
}
