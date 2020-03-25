<?php

namespace App\Http\Controllers;

use App\Soap\SoapClientEx;
use SoapFault;

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
            $client = new SoapClientEx($url, $opts, $attributes);
            return ['response' => $client->$method($data)];
        }catch(SoapFault $fault){
            return [
                'fault' => true,
                'message' => $fault->getMessage(),
            ];
        } catch (\Exception $exception) {
            return [
                'fault' => true,
                'message' => $exception->getMessage(),
            ];
        }
    }
}

