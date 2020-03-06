<?php


namespace App\Services\Company\Renessans;


use App\Contracts\Company\Renessans\RenessansCalculateServiceContract;
use App\Models\InsuranceCompany;
use App\Services\Company\CompanyCalculateService;
use GuzzleHttp\Client;

class RenessansCalculateCalculateService extends CompanyCalculateService implements RenessansCalculateServiceContract
{
    private $apiUrl;
    private $secretKey;

    private $catalogPurpose = []; // todo: значение из справочника, справочник нужно прогружать при валидации, будет кэшироваться
    private $catalogTypeOfDocument = [];

    private $apiPath = [
        'calculate' => '/calculate/?fullInformation=true',
        'create' => '/',
        'getStatus' => '/',
        'getCatalog' => '/',
    ];

    public function __construct()
    {
        $this->apiUrl = config('api_sk.renessans.apiUrl');
        $this->secretKey = config('api_sk.renessans.apiKey');
        if (!($this->apiUrl && $this->secretKey)) {
            throw new \Exception('renessans api is not configured');
        }
    }

    private function setAuth(&$attributes)
    {
        $attributes['key'] = $this->secretKey;
    }

    private  function getUrl($method)
    {
        if (!array_key_exists($method, $this->apiPath)) {
            throw new \Exception('not found api path');
        }
        return (substr($this->apiUrl, -1) == '/' ? substr($this->apiUrl, 0, -1) : $this->apiUrl) .
            $this->apiPath[$method];
    }

    public function run(InsuranceCompany $company, $attributes): array
    {
        $this->setAuth($attributes);
        $url = $this->getUrl(__FUNCTION__);
        $response = $this->postRequest($url, $attributes);
        return ['calculate', __CLASS__, $url, $attributes, $response];
    }

    public function postRequest($url, $data = [], $headers = [])
    {
        $client = new Client();
        $params = [];
        if ($headers and count($headers)) {
            $params['headers'] = $headers;
        }
        if ($data and count($data)) {
            $params['form_params'] = $data;
        }
        $response = $client->post($url,  $params);
        return \GuzzleHttp\json_decode($response->getBody()->getContents(), true);
    }

    public function map(): array
    {
        return [
            'dateStart' => [
                'required' => true,
                'type' => 'date',
                'format' => 'Y-m-d',
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
                        'format' => 'Y-m-d',
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
                                'format' => 'Y-m-d',
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
                        'in' => $this->catalogTypeOfDocument,
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
                                'type' => 'integer',
                            ],
                        ],
                    ],
                ],
            ],
            'usagePeriod' => [
                'required' => true,
                'type' => 'array',
                'array' => [
                    'drivers' => [
                        'required' => true,
                        'type' => 'object',
                        'array' => [
                            'dateStart' => [
                                'required' => true,
                                'type' => 'date',
                                'format' => 'Y-m-d',
                            ],
                            'dateEnd' => [
                                'required' => true,
                                'type' => 'date',
                                'format' => 'Y-m-d',
                            ],
                        ],
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
                        'format' => 'Y-m-d',
                    ],
                    'license' => [
                        'required' => true,
                        'type' => 'object',
                        'array' => [
                            'dateBeginDrive' => [
                                'required' => true,
                                'type' => 'date',
                                'format' => 'Y-m-d',
                            ],
                            'dateIssue' => [
                                'required' => true,
                                'type' => 'date',
                                'format' => 'Y-m-d',
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
