<?php

namespace App\Http\Controllers;

use SoapClient;
use SoapFault;
use SoapHeader;

class SoapController
{
    public static function requestBySoap($url, $method, $data = [], $headers = [])
    {
        try {
            $opts = [
                'trace'=>1,
                'stream_context' => stream_context_create([
                    'ssl' => [
                        'verify_peer' => false,
                        'verify_peer_name' => false,
                        'allow_self_signed' => true
                    ]
                ])];
            $client = new SoapClient($url, $opts);
            if ($headers && count($headers)) {
                foreach ($headers as $header) {
                    $h = new SoapHeader('http://schemas.xmlsoap.org/soap/envelope/', $header['name'], $header['value']);
                    $client->__setSoapHeaders($h);
                }
            }
            return $client->$method($data);
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
