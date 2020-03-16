<?php

namespace App\Http\Controllers;

use App\Soap\SoapClientEx;
//use SoapClient;
use SoapFault;
use SoapHeader;

class SoapController
{
    public static function requestBySoap($url, $method, $data = [], $auth = [], $headers = [], $attributes = [])
    {
        try {
            $opts = [
                'trace'=>1,
            ];
            if ($auth) {
                $opts['login'] = $auth['login'];
                $opts['password'] = $auth['password'];
            }
            $stream_context = [
                'ssl' => [
                    'verify_peer' => false,
                    'verify_peer_name' => false,
                    'allow_self_signed' => true
                ]
            ];
            if ($headers) {
                $stream_context['header'] = [];
                foreach ($headers as $header) {
                    $stream_context['header'][] = $header['name'] . ': ' . $header['value'];
                }
            }
            $opts['stream_context'] = stream_context_create($stream_context);
            //$client = new SoapClient($url, $opts);
            $client = new SoapClientEx($url, $opts, $attributes);
//            if ($method == "CalcProduct") {
//                dd($method, 'ok', $client->$method($data), $client->__getLastRequest(), $client, $data);
//            }
            //dd($method, 'ok', $client->$method($data), 'request', $client->__getLastRequest(), 'response', $client->__getLastResponse(), $client, $data);
            return ['response' => $client->$method($data)];
        }catch(SoapFault $fault){
            dd($method, 'fault',$fault,$client->__getLastRequest(), $client, $data);
            return [
                'fault' => true,
                'message' => $fault->getMessage(),
            ];
        } catch (\Exception $exception) {
            dd($method, 'exception',$exception, $client->__getLastRequest(), $client, $data);
            return [
                'fault' => true,
                'message' => $exception->getMessage(),
            ];
        }
    }
}

