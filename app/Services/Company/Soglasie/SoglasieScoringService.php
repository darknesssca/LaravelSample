<?php


namespace App\Services\Company\Soglasie;

use App\Contracts\Company\Soglasie\SoglasieScoringServiceContract;
use App\Http\Controllers\SoapController;
use App\Models\InsuranceCompany;
use App\Models\IntermediateData;

class SoglasieScoringService extends SoglasieService implements SoglasieScoringServiceContract
{

    private $catalogPurpose = ["Личная", "Такси"]; // TODO: значение из справочника, справочник нужно прогружать при валидации, будет кэшироваться
    private $catalogTypeOfDocument = []; // TODO: значение из справочника, справочник нужно прогружать при валидации, будет кэшироваться
    private $catalogCatCategory = ["A", "B"]; // TODO: значение из справочника, справочник нужно прогружать при валидации, будет кэшироваться

    public function __construct()
    {
        $this->apiWsdlUrl = config('api_sk.soglasie.kbmWsdlUrl');
        parent::__construct();
    }

    public function run($company, $attributes, $additionalFields = []): array
    {
        return $this->sendKbm($company, $attributes);
    }

    private function sendKbm($company, $attributes): array
    {
        $data = $this->prepareData($attributes);
        $headers = $this->getHeaders();
        $response = SoapController::requestBySoap($this->apiWsdlUrl, 'Login', $data, $headers);
        dd($response);
        if (!$response) {
            throw new \Exception('api not return answer');
        }
        if (isset($response['fault']) && $response['fault']) {
            throw new \Exception('api return '.isset($response['message']) ? $response['message'] : 'no message');
        }
//        if (!isset($response->response->error) || ($response->response->ErrorList->ErrorInfo->Code != 3)) { // согласно приведенному примеру 3 является кодом успешного ответа
//            throw new \Exception('api not return error Code: '.
//                isset($response->response->ErrorList->ErrorInfo->Code) ? $response->response->ErrorList->ErrorInfo->Code : 'no code | message: '.
//                isset($response->response->ErrorList->ErrorInfo->Message) ? $response->response->ErrorList->ErrorInfo->Message : 'no message');
//        }
        if (!isset($response->response->scoringid) || !$response->response->scoringid) {
            throw new \Exception('api not return IdRequestCalc');
        }
        return [
            'scoringId' => $response->response->scoringid,
        ];
    }

    protected function getHeaders()
    {
        return [
            'Authorization' => base64_encode($this->apiUser . "::" . $this->apiPassword),
            'Content-Type' => 'application/xml',
            'Accept' => 'application/xml',
        ];
    }

    public function prepareData($attributes)
    {
        $data = [
            'request' => [
                'test' => $this->transformBoolean($this->apiIsTest),
                'partial' => $this->transformBoolean(false),
                '_' => [
                    'private' => [],
                ],
            ],
        ];
        //private
        $owner = $this->searchSubjectById($attributes, $attributes['policy']['ownerId']);
        if ($owner) {
            $data['request']['_']['private'] = [
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
//                $pSubject[$iDocument.':document'] = $pDocument;
                $data['request']['_']['private']['documents']['document'][] = $pDocument;
            }
            foreach ($owner['addresses'] as $iAddress => $address) {
                $pAddress = [
                    'addressType' => $address['address']['addressType'],  // TODO: справочник
                    'address' => $address['address']['country'] . ', ' .
                    $address['address']['region'] . ', ' .
                    $address['address']['district'] . ', ' .
                    isset($address['address']['city']) ? $address['address']['city'] : $address['address']['populatedCenter'] . ', ' .
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
                $data['request']['_']['private']['addresses']['address'][] = $pAddress;
            }
        }
        return $data;
    }



}
