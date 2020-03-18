<?php


namespace App\Soap;


use SoapClient;

class SoapClientEx extends SoapClient
{
    public static $attributes = [];

    function __construct ($wsdl, array $options = null, $replace = [])
    {
        self::$attributes = $replace;
        return parent::__construct ($wsdl, $options);
    }

    function __doRequest($request, $location, $action, $version, $one_way = NULL) {

        $modRequest = $request;
        foreach (self::$attributes as $field => $attributes) {
            $matches = [];
            $count = preg_match_all('/<(?(?=[a-z\d]+:)[a-z\d]+:|)'.$field.'[\s|>]/', $modRequest, $matches);
            if ($count) {
                $target = [];
                $source = [];
                for ($i = 0; $i < $count; $i++) {
                    $source[] = $matches[0][$i];
                    $parameter = substr($matches[0][$i], 0, -1);
                    $lastPart = substr($matches[0][$i], -1);
                    foreach ($attributes as $name => $value) {
                        $parameter .= ' '.$name.'="';
                        switch ($value) {
                            case '@number':
                                $parameter .= ($i + 1);
                                break;
                            default:
                                $parameter .= (gettype($value) == 'boolean' ? ($value ? 'true' : 'false') : (string)$value);
                                break;
                        }
                        $parameter .= '"' . $lastPart;
                        $target[] = $parameter;
                    }
                    $modRequest = str_replace($source, $target, $modRequest);
                }

            }
        }
        //dd($modRequest, $request);
        return parent::__doRequest($modRequest, $location, $action, $version, $one_way);
    }
}
