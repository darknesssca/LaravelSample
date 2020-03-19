<?php


namespace App\Services\Company\Renessans;


use App\Contracts\Company\Renessans\RenessansCreateServiceContract;
use App\Models\InsuranceCompany;

class RenessansCreateService extends RenessansService implements RenessansCreateServiceContract
{
    protected $apiPath = [
        'sendCreate' => '/create/',
        //'receiveCalculate' => '/policy/:policyId/status/',
    ];

    private $catalogPurpose = ["Личная", "Такси"]; // TODO: значение из справочника, справочник нужно прогружать при валидации, будет кэшироваться
    private $catalogTypeOfDocument = [31, 32, 30]; // TODO: значение из справочника, справочник нужно прогружать при валидации, будет кэшироваться
    private $catalogStsDocType = []; // TODO: значение из справочника, справочник нужно прогружать при валидации, будет кэшироваться

    private function setAdditionalFields(&$attributes, $additionalFields) {
        $attributes['CheckSegment'] = intval(isset($additionalFields['isCheckSegment']) && $additionalFields['isCheckSegment']);
    }

    private function setCalculationId(&$attributes, $calculationId) {
        $attributes['calculationId'] = $calculationId;
    }

    public function run(InsuranceCompany $company, $attributes, $additionalFields = []): array
    {
        $this->setAdditionalFields($attributes, $additionalFields);
        $result = [];
        foreach ($additionalFields['calculationId'] as $calculationId) {
            $result[$calculationId] = $this->sendCreate($attributes, $calculationId);
        }
        return $result;
    }

    private function sendCreate($attributes, $calculationId)
    {
        $this->setCalculationId($attributes, $calculationId);
        $this->setAuth($attributes);
        $url = $this->getUrl(__FUNCTION__);
        $this->prepareData($attributes);
        $response = $this->postRequest($url, $attributes);
        if (!$response) {
            throw new \Exception('api not return answer');
        }
        if (!$response['result']) {
            throw new \Exception('api return '.isset($response['message']) ? $response['message'] : 'no message');
        }
        return $response['data'];
    }

    public function map(): array
    {
        return [
            'purpose' => [
                'required' => true,
                'type' => 'string',
                'in' => $this->catalogPurpose,
            ],
            'isInsurerJuridical' => [
                'required' => false,
                'type' => 'boolean',
                'default' => 0,
            ],
            'cabinet' => [
                'required' => true,
                'type' => 'object',
                'array' => [
                    'email' => [
                        'required' => true,
                        'type' => 'email',
                    ],
                ],
            ],
            'owner' => [
                'required' => true,
                'type' => 'object',
                'array' => [
                    'email' => [
                        'required' => true,
                        'type' => 'email',
                    ],
                    'phone' => [
                        'required' => true,
                        'type' => 'integer',
                    ],
                    'addressJuridical' => [
                        'required' => true,
                        'type' => 'object',
                        'array' => [
                            'country' => [
                                'required' => true,
                                'type' => 'string',
                            ],
                            'zip' => [
                                'required' => true,
                                'type' => 'integer',
                            ],
                            'city' => [
                                'required' => false,
                                'type' => 'string',
                            ],
                            'settlement' => [
                                'required_without' => 'owner.addressJuridical.city',
                                'type' => 'string',
                            ],
                            'street' => [
                                'required' => true,
                                'type' => 'string',
                            ],
                            'home' => [
                                'required' => true,
                                'type' => 'string',
                            ],
                            'flat' => [
                                'required' => true,
                                'type' => 'string',
                            ],
                            'kladr' => [
                                'required' => true,
                                'type' => 'integer',
                            ],
                        ],
                    ],
                    'addressFact' => [
                        'required' => true,
                        'type' => 'object',
                        'array' => [
                            'country' => [
                                'required' => true,
                                'type' => 'string',
                            ],
                            'zip' => [
                                'required' => true,
                                'type' => 'integer',
                            ],
                            'city' => [
                                'required' => false,
                                'type' => 'string',
                            ],
                            'settlement' => [
                                'required_without' => 'owner.addressFact.city',
                                'type' => 'string',
                            ],
                            'street' => [
                                'required' => true,
                                'type' => 'string',
                            ],
                            'home' => [
                                'required' => true,
                                'type' => 'string',
                            ],
                            'flat' => [
                                'required' => true,
                                'type' => 'string',
                            ],
                            'kladr' => [
                                'required' => true,
                                'type' => 'integer',
                            ],
                        ],
                    ],
                    'document' => [
                        'required' => false,
                        'type' => 'object',
                        'array' => [
                            'typeofdocument' => [
                                'required' => false,
                                'type' => 'string',
                                'in' => $this->catalogTypeOfDocument,
                            ],
                            'dateIssue' => [
                                'required' => true,
                                'type' => 'date',
                                'date_format' => 'Y-m-d',
                            ],
                            'issued' => [
                                'required' => true,
                                'type' => 'string',
                            ],
                            'number' => [
                                'required' => true,
                                'type' => 'string',
                            ],
                            'series' => [
                                'required' => true,
                                'type' => 'string',
                            ],
                            'codeDivision' => [
                                'required' => false,
                                'type' => 'integer',
                            ],
                        ],
                    ],
                ],
            ],
            'insurer' => [
                'required' => true,
                'type' => 'object',
                'array' => [
                    'name' => [
                        'required' => true,
                        'type' => 'string',
                    ],
                    'lastname' => [
                        'required' => true,
                        'type' => 'string',
                    ],
                    'middlename' => [
                        'required' => false,
                        'type' => 'string',
                    ],
                    'birthday' => [
                        'required' => true,
                        'type' => 'date',
                        'date_format' => 'Y-m-d',
                    ],
                    'email' => [
                        'required' => true,
                        'type' => 'email',
                    ],
                    'phone' => [
                        'required' => true,
                        'type' => 'integer',
                    ],
                    'addressJuridical' => [
                        'required' => true,
                        'type' => 'object',
                        'array' => [
                            'country' => [
                                'required' => true,
                                'type' => 'string',
                            ],
                            'zip' => [
                                'required' => true,
                                'type' => 'integer',
                            ],
                            'city' => [
                                'required' => false,
                                'type' => 'string',
                            ],
                            'settlement' => [
                                'required_without' => 'insurer.addressJuridical.city',
                                'type' => 'string',
                            ],
                            'street' => [
                                'required' => true,
                                'type' => 'string',
                            ],
                            'home' => [
                                'required' => true,
                                'type' => 'string',
                            ],
                            'flat' => [
                                'required' => true,
                                'type' => 'string',
                            ],
                            'kladr' => [
                                'required' => true,
                                'type' => 'integer',
                            ],
                        ],
                    ],
                    'addressFact' => [
                        'required' => true,
                        'type' => 'object',
                        'array' => [
                            'country' => [
                                'required' => true,
                                'type' => 'string',
                            ],
                            'zip' => [
                                'required' => true,
                                'type' => 'integer',
                            ],
                            'city' => [
                                'required' => false,
                                'type' => 'string',
                            ],
                            'settlement' => [
                                'required_without' => 'insurer.addressFact.city',
                                'type' => 'string',
                            ],
                            'street' => [
                                'required' => true,
                                'type' => 'string',
                            ],
                            'home' => [
                                'required' => true,
                                'type' => 'string',
                            ],
                            'flat' => [
                                'required' => true,
                                'type' => 'string',
                            ],
                            'kladr' => [
                                'required' => true,
                                'type' => 'integer',
                            ],
                        ],
                    ],
                    'document' => [
                        'required' => true,
                        'type' => 'object',
                        'array' => [
                            'typeofdocument' => [
                                'required' => false,
                                'type' => 'string',
                                'in' => $this->catalogTypeOfDocument,
                            ],
                            'dateIssue' => [
                                'required' => true,
                                'type' => 'date',
                                'date_format' => 'Y-m-d',
                            ],
                            'issued' => [
                                'required' => true,
                                'type' => 'string',
                            ],
                            'number' => [
                                'required' => true,
                                'type' => 'string',
                            ],
                            'series' => [
                                'required' => true,
                                'type' => 'string',
                            ],
                        ],
                    ],
                ],
            ],
            'car' => [
                'required' => true,
                'type' => 'object',
                'array' => [
                    'year' => [
                        'required' => true,
                        'type' => 'integer',
                    ],
                ],
                'pts' => [
                    'required_without' => 'car.sts',
                    'type' => 'object',
                    'array' => [
                        'dateIssue' => [
                            'required' => true,
                            'type' => 'date',
                            'date_format' => 'Y-m-d',
                        ],
                        'number' => [
                            'required' => true,
                            'type' => 'string',
                        ],
                        'serie' => [
                            'required' => true,
                            'type' => 'string',
                        ],
                    ],
                ],
                'sts' => [
                    'required_without' => 'car.pts',
                    'type' => 'object',
                    'array' => [
                        'dateIssue' => [
                            'required' => true,
                            'type' => 'date',
                            'date_format' => 'Y-m-d',
                        ],
                        'number' => [
                            'required' => true,
                            'type' => 'string',
                        ],
                        'serie' => [
                            'required' => true,
                            'type' => 'string',
                        ],
                        'docType' => [
                            'required' => true,
                            'type' => 'string',
                            'in' => $this->catalogStsDocType,
                        ],
                    ],
                ],
            ],
            'diagnostic' => [
                'required' => false,
                'type' => 'object',
                'array' => [
                    'number' => [
                        'required' => true,
                        'type' => 'string',
                    ],
                    'validDate' => [
                        'required' => true,
                        'type' => 'date',
                        'date_format' => 'Y-m-d',
                    ],
                ],
            ],
        ];
    }

}
