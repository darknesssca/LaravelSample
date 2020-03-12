<?php

namespace App\Http\Controllers;

use SoapClient;
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
            $client = new SoapClientNS($url, $opts, $attributes);
//            if ($method == "CalcProduct") {
//                dd($method, 'ok', $client->$method($data), $client->__getLastRequest(), $client, $data);
//            }
            //dd($method, 'ok', $client->$method($data), $client->__getLastRequest(), $client, $data);
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

class SoapClientNS extends SoapClient {

    public static $attributes = [];

    function __construct ($wsdl, array $options = null, $replace = [])
    {
        self::$attributes = $replace;
        return parent::__construct ($wsdl, $options);
    }

    function __doRequest($request, $location, $action, $version, $one_way = NULL) {

        $modRequest = $request;
        foreach (self::$attributes as $field => $attribute) {
            $parameter = '<'.$field;
            foreach ($attribute as $name => $value) {
                $parameter .= ' '.$name.'="'.(gettype($value) == 'boolean' ? ($value ? 'true' : 'false') : (string)$value).'"';
            }
            $modRequest = str_replace('<'.$field, $parameter, $modRequest);
        }
        //dd($request, $modRequest);
        return parent::__doRequest($modRequest, $location, $action, $version, $one_way);
    }
}
