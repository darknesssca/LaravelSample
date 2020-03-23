<?php


namespace App\Services\Company\Soglasie;

use App\Contracts\Company\Soglasie\SoglasieScoringServiceContract;
use App\Http\Controllers\SoapController;

class SoglasieScoringService extends SoglasieService implements SoglasieScoringServiceContract
{

    private $catalogPurpose = ["Личная", "Такси"]; // TODO: значение из справочника, справочник нужно прогружать при валидации, будет кэшироваться
    private $catalogTypeOfDocument = []; // TODO: значение из справочника, справочник нужно прогружать при валидации, будет кэшироваться
    private $catalogCatCategory = ["A", "B"]; // TODO: значение из справочника, справочник нужно прогружать при валидации, будет кэшироваться

    public function __construct()
    {
        $this->apiWsdlUrl = config('api_sk.soglasie.scoringWsdlUrl');
        if (!($this->apiWsdlUrl)) {
            throw new \Exception('soglasie api is not configured');
        }
        parent::__construct();
    }

    public function run($company, $attributes, $additionalFields = []): array
    {
        $data = $this->prepareData($attributes);
        $headers = $this->getHeaders();
        $auth = $this->getAuth();
        $xmlAttributes = [
            'request' => [
                'test' => $this->transformBoolean($this->apiIsTest),
                'partial' => $this->transformBoolean(false),
            ],
        ];
        $response = SoapController::requestBySoap($this->apiWsdlUrl, 'getScoringId', $data, $auth, $headers, $xmlAttributes);
        if (!$response) {
            throw new \Exception('api not return answer');
        }
        if (isset($response['fault']) && $response['fault']) {
            throw new \Exception('api return '.isset($response['message']) ? $response['message'] : 'no message');
        }
        if (!isset($response['response']->response->scoringid) || !$response['response']->response->scoringid) {
            throw new \Exception('api not return scoringid');
        }
        return [
            'scoringId' => $response['response']->response->scoringid,
        ];
    }

    protected function getHeaders()
    {
        return [
            [
                'name' => 'Content-Type',
                'value' => 'application/xml',
            ],
            [
                'name' => 'Accept',
                'value' => 'application/xml',
            ],
        ];
    }

    public function prepareData($attributes)
    {
        $data = [
            'request' => [
                'private' => [],
            ],
        ];
        //private
        $owner = $this->searchSubjectById($attributes, $attributes['policy']['ownerId']);
        if ($owner) {
            $data['request']['private'] = [
                "lastname" => $owner['lastName'],
                "firstname" => $owner['firstName'],
                "middlename" => isset($owner['middleName']) ? $owner['middleName'] : '',
                "birthday" => $owner['birthdate'],
                "birthplace" => $owner['birthPlace'],
                'documents' => [
                    'document' => [],
                ],
                'addresses' => [
                    'address' => [],
                ],
                "sex" => $owner['firstName'], // todo из справочника
                "note" => '',
            ];
            foreach ($owner['documents'] as $iDocument => $document) {
                $pDocument = [
                    'doctype' => $document['document']['documentType'],  // TODO: справочник
                    'docseria' => isset($document['document']['documentType']) ? $document['document']['documentType'] : '',
                ];
                $this->setValuesByArray($pDocument, [
                    "docnumber" => 'number',
                    "docplace" => 'issuedBy',
                    "docdatebegin" => 'dateIssue',
                ], $document['document']);
                $data['request']['private']['documents']['document'][] = $pDocument;
            }
            foreach ($owner['addresses'] as $iAddress => $address) {
                $pAddress = [
                    'type' => $address['address']['addressType'] , // TODO: справочник
                    'address' => $address['address']['country'] . ', ' . $address['address']['region'] . ', ' .
                        $address['address']['district'] . ', ' .
                        (isset($address['address']['city']) ? $address['address']['city'] : $address['address']['populatedCenter']) . ', ' .
                        $address['address']['street'] . ', ' .
                        $address['address']['building'] . ', ' .
                        $address['address']['flat'],
                    'city' => isset($address['address']['city']) ? $address['address']['city'] : $address['address']['populatedCenter'],
                    'street' => $address['address']['street'],
                    'house' => $address['address']['building'],
                    'flat' => $address['address']['flat'],
                ];
                $this->setValuesByArray($pAddress, [
                    "index" => 'postCode',
                    "region" => 'region',
                    "zone" => 'district',
                ], $address['address']);
                $data['request']['private']['addresses']['address'][] = $pAddress;
            }
        }
        return $data;
    }



}
