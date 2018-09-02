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
        $welcome = ['olá', 'ola', 'quero', 'ver', 'documentos', 'debitos', 'débitos', 'veículo', 'carro', 'meu', 'veiculo'];
        $payment = ['BB', 'banco do brasil', 'atar'];

        $body = $request->entry[0]["messaging"][0];
        $sender_id = $body["sender"]["id"];
        $message = utf8_encode($body["message"]["text"]);

        if (strlen($message) == 7) {
            $value = $this->getDebits($message);

            $conversa = Conversas::getBySender($sender_id);
            if (is_null($conversa))
                $conversa = new Conversas();

            $conversa->sender = $sender_id;
            $conversa->placa = $message;
            $conversa->debitos = $value;
            $conversa->save();

            return $this->buildMessage($sender_id, self::DEBITS_MESSAGE . $value . '. ' . self::PAYMENT_METHOD_MESSAGE);
        } else {
            if  (in_array(strtolower($message), $payment)) {
                $conversa = Conversas::getBySender($sender_id);

                $value_format = $conversa->debitos * 100;
                $link = $this->paymentAtar($value_format);

                return $this->buildMessage($sender_id, self::PAYMENT_MESSAGE . $link);
            } else {
                return $this->buildMessage($sender_id, self::PLATE_MESSAGE);
            }
        }

//
//        if (in_array(strtolower($message), $welcome)) {
//            return $this->buildMessage($sender_id, self::PLATE_MESSAGE);
//        } else {
//            if (!in_array(strtolower($message), $payment)) {
//
//                if (strlen($message) == 7) {
//                    return $this->buildMessage($sender_id, self::DEBITS_MESSAGE . $this->getDebits($message));
//                } else {
//                    return $this->buildMessage($sender_id, self::PLATE_MESSAGE);
//                }
////                return $this->buildMessage($sender_id, self::PAYMENT_METHOD_MESSAGE);
//            } else {
//                return $this->buildMessage($sender_id, self::PAYMENT_MESSAGE);
//            }
//        }
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
        $retorno = curl_exec($curl);
        $info = curl_getinfo($curl);
        curl_close($curl);

        Log::info("======================================================================");
        Log::warning("STATUS_CODE " . $info['http_code']);
        Log::warning("CURL " . $retorno);

    }

    private function getDebits($plate)
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

        return $valor_total;

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

    public function test($test)
    {
        dd(strlen($test));
    }
}
