<?php


namespace App\Services\Company\Soglasie;

use App\Contracts\Company\Soglasie\SoglasieKbmServiceContract;
use App\Http\Controllers\SoapController;
use App\Models\InsuranceCompany;
use App\Models\IntermediateData;

class SoglasieKbmService extends SoglasieService implements SoglasieKbmServiceContract
{

    private $catalogPurpose = ["Личная", "Такси"]; // TODO: значение из справочника, справочник нужно прогружать при валидации, будет кэшироваться
    private $catalogTypeOfDocument = []; // TODO: значение из справочника, справочник нужно прогружать при валидации, будет кэшироваться
    private $catalogCatCategory = ["A", "B"]; // TODO: значение из справочника, справочник нужно прогружать при валидации, будет кэшироваться

    public function __construct()
    {
        $this->apiWsdlUrl = config('api_sk.soglasie.kbmWsdlUrl');
        if (!($this->apiWsdlUrl)) {
            throw new \Exception('soglasie api is not configured');
        }
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
        $auth = $this->getAuth();
        $response = SoapController::requestBySoap($this->apiWsdlUrl, 'getKbm', $data, $auth, $headers);
        dd($response);
        if (!$response) {
            throw new \Exception('api not return answer');
        }
        if (isset($response['fault']) && $response['fault']) {
            throw new \Exception('api return '.isset($response['message']) ? $response['message'] : 'no message');
        }
        if (!isset($response->response->ErrorList->ErrorInfo->Code) || ($response->response->ErrorList->ErrorInfo->Code != 3)) { // согласно приведенному примеру 3 является кодом успешного ответа
            throw new \Exception('api not return error Code: '.
                isset($response->response->ErrorList->ErrorInfo->Code) ? $response->response->ErrorList->ErrorInfo->Code : 'no code | message: '.
                isset($response->response->ErrorList->ErrorInfo->Message) ? $response->response->ErrorList->ErrorInfo->Message : 'no message');
        }
        if (!isset($response->response->IdRequestCalc) || !$response->response->CalcResponseValue->IdRequestCalc) {
            throw new \Exception('api not return IdRequestCalc');
        }
        return [
            'kbmId' => $response->response->CalcResponseValue->IdRequestCalc,
        ];
    }

    public function prepareData($attributes)
    {
        $data = [
            'request' => [
                'CalcRequestValue' => [
                    'InsurerID' => '000-241790',
                    'CalcKBMRequest' => [
                        'CarIdent' => [
                            'VIN' => $attributes['car']['vin'],
                        ],
                        'DriversRestriction' => $this->transformBoolean($attributes['policy']['isMultidrive']),
                        'DateKBM' => $this->formatDateTime($attributes['policy']['beginDate']),
                        'PhysicalPersons' => [
                            'PhysicalPerson' => [],
                        ],
                    ],
                ],
            ],
        ];
        //PhysicalPerson
        foreach ($attributes['subjects'] as $iSubject => $subject) {
            if ($subject['id'] != $attributes['policy']['ownerId']) {
                continue;
            }
            $pSubject = [];
            foreach ($subject['fields']['documents'] as $iDocument => $document) {
                $pDocument = [];
                if ($document['document']['documentType'] != 'driverLicense') {
                    $pDocument['DocPerson'] = $document['document']['documentType'];  // TODO: справочник
                }
                $this->setValuesByArray($pDocument, [
                    "Serial" => 'series',
                    "Number" => 'number',
                ], $document['document']);
                $targetName = '';
                switch ($document['document']['documentType']) {
                    case 'driverLicense':
                        $targetName = 'DriverDocument';
                        break;
                    case 'passport':
                        $targetName = 'PersonDocument';
                        break;
                    default:
                        $targetName = 'PersonDocumentAdd';
                        break;
                }
                $pSubject[$targetName] = $pDocument;
                $pSubject['PersonNameBirthHash'] = '### '.
                    $subject['fields']['lastName'] . '|'.
                    $subject['fields']['firstName'] . '|'.
                    (isset($subject['fields']['middleName']) ? $subject['fields']['middleName'] : '') . '|' .
                    $this->formatDateToRuFormat($subject['fields']['birthdate']);
            }
            $data['request']['CalcRequestValue']['CalcKBMRequest']['PhysicalPersons']['PhysicalPerson'][] = $pSubject;
        }
        return $data;
    }

}
