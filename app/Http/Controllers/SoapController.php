<?php

namespace App\Http\Controllers;

use SoapClient;
use SoapFault;

class SoapController
{
    public static function requestBySoap($url, $method, $data = [])
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
