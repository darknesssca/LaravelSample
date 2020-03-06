<?php


namespace App\Services\Company\Renessans;


use App\Contracts\Company\Renessans\RenessansCreateServiceContract;
use App\Models\InsuranceCompany;

class RenessansCreateService extends RenessansService implements RenessansCreateServiceContract
{
    private $apiPath = [
        'sendCalculate' => '/create/',
        'receiveCalculate' => '/policy/:policyId/status/',
    ];

    private $catalogPurpose = ["Личная", "Такси"]; // TODO: значение из справочника, справочник нужно прогружать при валидации, будет кэшироваться
    private $catalogTypeOfDocument = []; // TODO: значение из справочника, справочник нужно прогружать при валидации, будет кэшироваться
    private $catalogCatCategory = ["A", "B"]; // TODO: значение из справочника, справочник нужно прогружать при валидации, будет кэшироваться

    public function run(InsuranceCompany $company, $attributes, $additionalFields = []): array
    {
        $data = $this->sendCalculate($attributes);
        $calculatedData = [];
        foreach ($data as $calcData) {
            if (!isset($calcData['id'])) {
                continue;
            }
            $requestData = [
                'id' => $calcData['id'],
            ];
            $calculatedData[] = $this->receiveCalculate($requestData);
        }
        return $calculatedData;
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
