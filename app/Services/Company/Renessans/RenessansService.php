<?php


namespace App\Services\Company\Renessans;

use App\Services\Company\CompanyService;

abstract class RenessansService extends CompanyService
{
    private $apiUrl;
    private $secretKey;

    public function __construct()
    {
        $this->apiUrl = config('api_sk.renessans.apiUrl');
        $this->secretKey = config('api_sk.renessans.apiKey');
        if (!($this->apiUrl && $this->secretKey)) {
            throw new \Exception('renessans api is not configured');
        }
    }

    public function prepareData(&$data, $fields = null)
    {
        if (!$fields) {
            $fields = $this->map();
        }
        foreach ($fields as $field => $settings) {
            foreach ($settings as $parameter => $value) {
                switch ($parameter) {
                    case 'default':
                        if (!array_key_exists($field, $data)) {
                            $data[$field] = $value;
                        }
                        break;
                    case 'type':
                        if (($value == 'array') || ($value == 'object')) {
                            $this->prepareData($data[$field], $settings['array']);
                        } elseif ($value == 'boolean') {
                            if (array_key_exists($field, $data)) {
                                $data[$field] = (int)$data[$field];
                            }
                        }
                        break;
                }
            }
        }
    }

    protected function setAuth(&$attributes)
    {
        $attributes['key'] = $this->secretKey;
    }

    protected function getUrl($method, $data = null)
    {
        $url = (substr($this->apiUrl, -1) == '/' ? substr($this->apiUrl, 0, -1) : $this->apiUrl) .
            $this->apiPath[$method];
        if (!$data || !count($data)) {
            return $url;
        }
        foreach ($data as $key => $value) {
            $url = str_replace('{{'.$key.'}}', $value, $url);
        }
        return $url;
    }
}
