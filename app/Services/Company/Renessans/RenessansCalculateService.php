<?php


namespace App\Services\Company\Renessans;


use App\Contracts\Company\Renessans\RenessansCalculateServiceContract;
use App\Models\InsuranceCompany;

class RenessansCalculateService extends RenessansService implements RenessansCalculateServiceContract
{
    protected $apiPath = [
        'sendCalculate' => '/calculate/?fullInformation=true',
        'receiveCalculate' => '/calculate/{{id}}/',
    ];

    private $catalogPurpose = ["Личная", "Такси"]; // TODO: значение из справочника, справочник нужно прогружать при валидации, будет кэшироваться
    private $catalogTypeOfDocument = []; // TODO: значение из справочника, справочник нужно прогружать при валидации, будет кэшироваться
    private $catalogCatCategory = ["A", "B"]; // TODO: значение из справочника, справочник нужно прогружать при валидации, будет кэшироваться

    public function run(InsuranceCompany $company, $attributes, $additionalFields = []): array
    {
        $data = $this->sendCalculate($attributes);
        if (!($data && count($data))) {
            throw new \Exception('SK api not return data!');
        }
        $calculatedData = [];
        foreach ($data as $calcData) {
            if (!isset($calcData['id'])) {
                continue;
            }
            $requestData = [
                'id' => $calcData['id'],
            ];
            $calculatedData[] = [
                'calculationId' => $calcData['id'],
                'calculateData' => $this->receiveCalculate($requestData)
            ];
        }
        return $calculatedData;
    }

    private function sendCalculate($attributes): array
    {
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

    private function receiveCalculate($attributes)
    {
        $data = [];
        $this->setAuth($data);
        $url = $this->getUrl(__FUNCTION__, $attributes);
        $response = $this->getRequest($url, $data);
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
            'dateStart' => [
                'required' => true,
                'type' => 'date',
                'date_format' => 'Y-m-d',
            ],
            'period' => [
                'required' => false,
                'type' => 'integer',
                'default' => 12,
            ],
            'purpose' => [
                'required' => true,
                'type' => 'string',
                'in' => $this->catalogPurpose,
            ],
            'limitDrivers' => [
                'required' => true,
                'type' => 'boolean',
            ],
            'trailer' => [
                'required' => true,
                'type' => 'boolean',
            ],
            'isJuridical' => [
                'required' => false,
                'type' => 'boolean',
                'default' => 0,
            ],
            'codeKladr' => [
                'required' => true,
                'type' => 'string',
            ],
            'owner' => [
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
                    'make' => [
                        'required' => true,
                        'type' => 'string',
                    ],
                    'model' => [
                        'required' => true,
                        'type' => 'string',
                    ],
                    'MarkAndModelString' => [
                        'required' => true,
                        'type' => 'string',
                    ],
                    'category' => [
                        'required' => true,
                        'type' => 'string',
                        'in' => $this->catalogCatCategory,
                    ],
                    'power' => [
                        'required' => true,
                        'type' => 'integer',
                    ],
                    'UnladenMass' => [
                        'required' => false,
                        'required_if' => [
                            'field' => 'category',
                            'value' => [
                                'C',
                                'D',
                            ],
                        ],
                        'type' => 'integer',
                    ],
                    'ResolutionMaxWeight' => [
                        'required' => false,
                        'required_if' => [
                            'field' => 'category',
                            'value' => [
                                'C',
                                'D',
                            ],
                        ],
                        'type' => 'integer',
                    ],
                    'NumberOfSeats' => [
                        'required' => false,
                        'required_if' => [
                            'field' => 'category',
                            'value' => [
                                'C',
                                'D',
                            ],
                        ],
                        'type' => 'integer',
                    ],
                    'documents' => [
                        'required' => true,
                        'type' => 'object',
                        'array' => [
                            'vin' => [
                                'required' => true,
                                'type' => 'string',
                            ],
                        ],
                    ],
                ],
            ],
            'usagePeriod' => [
                'required' => true,
                'type' => 'array',
                'array' => [
                    'dateStart' => [
                        'required' => true,
                        'type' => 'date',
                        'date_format' => 'Y-m-d',
                    ],
                    'dateEnd' => [
                        'required' => true,
                        'type' => 'date',
                        'date_format' => 'Y-m-d',
                    ],
                ],
            ],
            'drivers' => [
                'required' => true,
                'type' => 'array',
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
                    'license' => [
                        'required' => true,
                        'type' => 'object',
                        'array' => [
                            'dateBeginDrive' => [
                                'required' => true,
                                'type' => 'date',
                                'date_format' => 'Y-m-d',
                            ],
                            'dateIssue' => [
                                'required' => true,
                                'type' => 'date',
                                'date_format' => 'Y-m-d',
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
        ];
    }

}
