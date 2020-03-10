<?php


namespace App\Services\Company;

use App\Models\InsuranceCompany;
use GuzzleHttp\Client;

class CompanyService implements CompanyServiceInterface
{

    public function run(InsuranceCompany $company, $attributes, $additionalData): array
    {
        //
    }

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

    public function validationRulesForm(): array
    {
        return [
            'token' => "required|string",
            'subjects' => "required|array",
            "subjects.*.id" => "required|integer",
            "subjects.*.fields.lastName" => "required|string",
            "subjects.*.fields.firstName" => "required|string",
            "subjects.*.fields.middleName" => "string",
            "subjects.*.fields.birthdate" => "required|date|format:Y-m-d",
            "subjects.*.fields.email" => "email",
            "subjects.*.fields.gender" => "required|string|in",
            "subjects.*.fields.citizenship" => "string|in",
            "subjects.*.fields.addresses" => "required|array",
            "subjects.*.fields.addresses.*.address.addressType" => "required|string|in",
            "subjects.*.fields.addresses.*.address.country" => "required|string|in",
            "subjects.*.fields.addresses.*.address.postCode" => "string",
            "subjects.*.fields.addresses.*.address.region" => "required|string|in",
            "subjects.*.fields.addresses.*.address.regionKladr" => "required|string",
            "subjects.*.fields.addresses.*.address.district" => "string",
            "subjects.*.fields.addresses.*.address.districtKladr" => "string",
            "subjects.*.fields.addresses.*.address.city" => "string",
            "subjects.*.fields.addresses.*.address.cityKladr" => "string",
            "subjects.*.fields.addresses.*.address.populatedCenter" => "string",
            "subjects.*.fields.addresses.*.address.populatedCenterKladr" => "string",
            "subjects.*.fields.addresses.*.address.street" => "string",
            "subjects.*.fields.addresses.*.address.streetKladr" => "string",
            "subjects.*.fields.addresses.*.address.building" => "string",
            "subjects.*.fields.addresses.*.address.buildingKladr" => "string",
            "subjects.*.fields.addresses.*.address.flat" => "string",
            "subjects.*.fields.document" => "required|array",
            "subjects.*.fields.document.*.documentType" => "required|string", // TODO: in справочник
            "subjects.*.fields.document.*.series" => "string",
            "subjects.*.fields.document.*.number" => "required|string",
            "subjects.*.fields.document.*.issuedBy" => "required|string",
            "subjects.*.fields.document.*.dateIssue" => "required|date|format:Y-m-d",
            "subjects.*.fields.document.*.validTo" => "date|format:Y-m-d",
            "subjects.*.fields.document.*.subdivisionCode" => "string",
            "subjects.*.fields.phone" => "required",
            "subjects.*.fields.phone.numberPhone" => "required|string",
            'car' => "required",
            "car.model" => "required|string", // TODO: in справочник
            "car.maker" => "required|string", // TODO: in справочник
            "car.enginePower" => "required|integer",
            "car.countryOfRegistration" => "required|string", // TODO: in справочник
            "car.isUsedWithTrailer" => "required|boolean",
            "car.mileage" => "required|integer",
            "car.sourceAcquisition" => "required|string", // TODO: in справочник
            "car.vehicleCost" => "required|integer",
            "car.vehicleUsage" => "required|string", // TODO: in справочник
            "car.vehicleUseRegion" => "required|string", // TODO: in справочник
            "car.isIrregularVIN" => "required|boolean",
            "car.vin" => "required|string",
            "car.year" => "required|string|min:4|max:4",
            "car.documents" => "required|array",
            "car.documents.*.document" => "required",
            "car.documents.*.document.documentType" => "required|string", // TODO: in справочник
            "car.documents.*.document.documentSeries" => "required|string", // TODO: in справочник
            "car.documents.*.document.documentNumber" => "required|string", // TODO: in справочник
            "car.documents.*.document.documentIssued" => "required|string", // TODO: in справочник
            'policy' => "required",
            'policy.beginDate' => "required|date|format:Y-m-d",
            'policy.insurantId' => "required|integer",
            'policy.ownerId' => "required|integer",
            'policy.isMultidrive' => "required|boolean",
            'drivers' => "required|array",
            'drivers.*.driver' => "required",
            'drivers.*.driver.driverId' => "integer",
        ];
    }

    public function validationMessagesForm(): array
    {
        return [];
    }

    public function validationRulesProcess(): array
    {
        return [
            'token' => "required|string",
        ];
    }

    public function validationMessagesProcess(): array
    {
        return [];
    }

    public function setValue(&$target, $targetName, $sourceName, $source)
    {
        if (isset($source[$sourceName]) && $source[$sourceName]) {
            $target[$targetName] = $source[$sourceName];
        }
    }

    public function setValuesByArray(&$target, $dependencies, $source)
    {
        foreach ($dependencies as $targetName => $sourceName) {
            if (isset($source[$sourceName]) && $source[$sourceName]) {
                if (typeof($source[$sourceName]) == 'array') {
                    continue;
                }
                $target[$targetName] = $source[$sourceName];
            }
        }
    }
}
