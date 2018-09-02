<?php

namespace App\Http\Controllers;

use App\Models\Conversas;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class FacebookController extends Controller
{
    const DEBITS_MESSAGE = 'O valor total dos débitos do seu veículo é: R$ ';
    const PLATE_MESSAGE = 'Qual a sua placa?';
    const PAYMENT_METHOD_MESSAGE = 'Quer pagar pelo ATAR pay ou fazer um TED pelo BB?';
    const PAYMENT_MESSAGE = 'Clique nesse link e efetue o pagamento: ';
    const NOT_FOUND = 'Não encontramos seu veículo. ';

    const CRITERIA_ATAR = ['atar', 'atar pay', 'atarpay', 'atar  pay'];
    const CRITERIA_BB = ['banco do brasil', 'bb'];

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

        $this->sendAsyncFacebook($this->verifyChat($request));

        return response()->json("EVENT_RECEIVED", 200);
    }

    private function verifyChat(Request $request)
    {
        $body = $request->entry[0]["messaging"][0];
        $sender_id = $body["sender"]["id"];
        $message = utf8_encode($body["message"]["text"]);

        return $this->sendChat($sender_id, $message);
    }

    private function sendChat($sender_id, $message)
    {
        if (strlen($message) == 7) {
            return $this->sendTotalDebitsMessage($message, $sender_id);
        } else {
            if (in_array(strtolower($message), self::CRITERIA_ATAR)) {
                return $this->sendPayWithAtarMessage($sender_id);
            }

            if (in_array(strtolower($message), self::CRITERIA_BB)) {
                return $this->buildMessage($sender_id, self::PAYMENT_MESSAGE . "http://www.bb.com.br/pbb/rapido?t=YT02Mjk3JmM9MTc2NDE5Jmg9MSZkPTA0MDcyMDE4JnY9MTAwMDAmdD0x");
            }

            return $this->buildMessage($sender_id, self::PLATE_MESSAGE);
        }
    }

    private function sendTotalDebitsMessage($message, $sender_id) {
        $value = $this->getDebits($message);
        $this->saveConversation($sender_id, $message, $value);
        return $this->buildMessage($sender_id, self::DEBITS_MESSAGE . $value . '. ' . self::PAYMENT_METHOD_MESSAGE);
    }

    private function sendPayWithAtarMessage($sender_id) {
        $conversa = Conversas::getBySender($sender_id);

        if (is_null($conversa))
            return $this->buildMessage($sender_id, self::NOT_FOUND . self::PLATE_MESSAGE);

        return $this->buildMessage($sender_id, self::PAYMENT_MESSAGE . $this->paymentAtar($conversa->debitos * 100));
    }

    private function saveConversation($sender_id, $message, $value)
    {
        $conversa = Conversas::getBySender($sender_id);
        if (is_null($conversa))
            $conversa = new Conversas();

        $conversa->sender = $sender_id;
        $conversa->placa = strtolower($message);
        $conversa->debitos = $value;
        $conversa->save();
    }

    private function buildMessage($sender_id, $message)
    {
        $object = new class{};
        $object->messaging_type = "RESPONSE";
        $recipient = new class{};
        $recipient->id = $sender_id;
        $message_obj = new class{};
        $message_obj->text = $message;

        $object->recipient = $recipient;
        $object->message = $message_obj;

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
        curl_close($curl);
    }

    private function getDebits($plate)
    {
        $url = "https://api.emplacai.towty.com.br/placa/" . $plate;
        $curl = curl_init($url);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        $retorno = json_decode(curl_exec($curl));

        $debits = $retorno->Conteudo->Debitos->DebitosDTO->Debito->DebitoDETRANNETDTO;

        return $this->getDebitsTotal($debits);
    }

    private function getDebitsTotal($debits) {
        $result = 0;
        if(is_array($debits)) {
            foreach ($debits as $debit) {
                if($debit->Tipo == "IPVA01") continue;
                $result += $debit->Valor;
            }
        } else {
            $result = $debits->Valor;
        }

        return $result;
    }

    private function paymentAtar($value)
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

        return $return->link;
    }
}
