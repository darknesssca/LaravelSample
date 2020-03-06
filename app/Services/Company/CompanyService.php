<?php


namespace App\Services\Company;

use App\Models\InsuranceCompany;
use GuzzleHttp\Client;

abstract class CompanyService implements CompanyServiceInterface
{

    abstract public function run(InsuranceCompany $company, $attributes, $additionalData): array;
    abstract public function map(): array;

    public function addRule(&$rule, $parameter = null, $value = null)
    {
        if (strlen($rule)) {
            $rule .= '|';
        }
        if ($parameter) {
            $rule .= $parameter;
        }
        if ($value) {
            if ($parameter) {
                $rule .= ':';
            }
            switch (gettype($value)) {
                case 'string':
                    $rule .= $value;
                    break;
                case 'array':
                    $rule .= array_shift($value);
                    $rule .= implode(',', $value);
                    break;
            }
        }
    }

    public function getRules($fields, $prefix = ''): array
    {
        $rules = [];
        foreach ($fields as $field => $settings) {
            $rule = '';
            foreach ($settings as $parameter => $value) {
                switch ($parameter) {
                    case 'required':
                        if ($value) {
                            $this->addRule($rule, $parameter);
                        } else {
                            continue;
                        }
                        break;
                    case 'required_if':
                        $array = $value['value'];
                        array_unshift($array, $value['field']);
                        $this->addRule($rule, $parameter, $array);
                        break;
                    case 'type':
                        if ($value == 'object') {
                            $this->addRule($rule, null, 'array');
                            $rules = array_merge($rules, self::getRules($settings['array'], $prefix.$field . '.'));
                        } elseif ($value == 'array') {
                            $this->addRule($rule, null, 'array');
                            $rules = array_merge($rules, self::getRules($settings['array'], $prefix.$field . '.*.'));
                        } else {
                            $this->addRule($rule, null, $value);
                        }
                        break;
                    case 'required_without':
                    case 'date_format':
                        $this->addRule($rule, $parameter, $value);
                        break;
                    case 'in':
                        $this->addRule($rule, $parameter, implode(',', $value));
                        break;
                }
            }
            $rules[$prefix . $field] = $rule;
        }
        return $rules;
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

    public function postRequest($url, $data = [], $headers = []): array
    {
        $client = new Client();
        $params = [];
        if ($headers and count($headers)) {
            $params['headers'] = $headers;
        }
        if ($data and count($data)) {
            $params['form_params'] = $data;
        }
        $response = $client->post($url, $params);
        return \GuzzleHttp\json_decode($response->getBody()->getContents(), true);
    }

    public function getRequest($url, $data = [], $headers = []): array
    {
        $client = new Client();
        $params = [];
        if ($headers and count($headers)) {
            $params['headers'] = $headers;
        }
        if ($data and count($data)) {
            $params['query'] = $data;
        }
        $response = $client->get($url, $params);
        return \GuzzleHttp\json_decode($response->getBody()->getContents(), true);
    }

    public function validationRules(): array
    {
        return $this->getRules($this->map());
    }

    public function validationMessages(): array
    {
        return [];
    }
}
