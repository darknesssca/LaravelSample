<?php


namespace App\Services\Company\Soglasie;

use App\Contracts\Company\Soglasie\SoglasieKbmServiceContract;
use App\Exceptions\ApiRequestsException;
use App\Exceptions\ConmfigurationException;
use App\Traits\DateFormat;
use App\Traits\TransformBoolean;

class SoglasieKbmService extends SoglasieService implements SoglasieKbmServiceContract
{
    use TransformBoolean, DateFormat;

    public function __construct()
    {
        $this->apiWsdlUrl = config('api_sk.soglasie.kbmWsdlUrl');
        if (!$this->apiWsdlUrl) {
            throw new ConmfigurationException('Ошибка конфигурации API ' . static::companyCode);
        }
        parent::__construct();
    }

    public function run($company, $attributes): array
    {
        $data = $this->prepareData($attributes);
        $headers = $this->getHeaders();
        $auth = $this->getAuth();
        $response = $this->requestBySoap($this->apiWsdlUrl, 'getKbm', $data, $auth, $headers);
        if (isset($response['fault']) && $response['fault']) {
            throw new ApiRequestsException(
                'API страховой компании вернуло ошибку: ' .
                isset($response['message']) ? $response['message'] : ''
            );
        }
        if (
            !isset($response['response']->response->ErrorList->ErrorInfo->Code) ||
            ($response['response']->response->ErrorList->ErrorInfo->Code != 3) // согласно приведенному примеру 3 является кодом успешного ответа
        ) {
            throw new ApiRequestsException([
                'API страховой компании вернуло некорректный код ответа (код ошибки)',
                isset($response['response']->response->ErrorList->ErrorInfo->Message) ?
                    $response['response']->response->ErrorList->ErrorInfo->Message :
                    'нет данных об ошибке',
            ]);
        }
        if (
            !isset($response['response']->response->CalcResponseValue->IdRequestCalc) ||
            !$response['response']->response->CalcResponseValue->IdRequestCalc
        ) {
            throw new ApiRequestsException([
                'API страховой компании не вернуло данных',
                isset($response['response']->response->ErrorList->ErrorInfo->Message) ?
                    $response['response']->response->ErrorList->ErrorInfo->Message :
                    'нет данных об ошибке',
            ]);
        }
        return [
            'kbmId' => $response['response']->response->CalcResponseValue->IdRequestCalc,
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
                        'DriversRestriction' => $this->transformAnyToBoolean(!$attributes['policy']['isMultidrive']),
                        'DateKBM' => $this->dateTimeFromDate($attributes['policy']['beginDate']),
                        'PhysicalPersons' => [
                            'PhysicalPerson' => [],
                        ],
                    ],
                ],
            ],
        ];
        //PhysicalPerson
        $owner = $this->searchSubjectById($attributes, $attributes['policy']['ownerId']);
        $pSubject = [];
        foreach ($owner['documents'] as $iDocument => $document) {
            $pDocument = [];
            if ($document['document']['documentType'] != 'driverLicense') {
                $pDocument['DocPerson'] = 20; //$document['document']['documentType'];  // TODO: справочник, ВАЖНО тут передается тоже driveLicense
            }
            $this->setValuesByArray($pDocument, [
                "Serial" => 'series',
                "Number" => 'number',
            ], $document['document']);
            $targetName = '';
            switch ($document['document']['documentType']) {
                case 'driverLicense': // TODO: справочник
                    $targetName = 'DriverDocument';
                    break;
                case 'passport': // TODO: справочник
                    $targetName = 'PersonDocument';
                    break;
                default:
                    $targetName = 'PersonDocumentAdd';
                    break;
            }
            $pSubject[$targetName] = $pDocument;
            $pSubject['PersonNameBirthHash'] = '### '.
                $owner['lastName'] . '|'.
                $owner['firstName'] . '|'.
                (isset($owner['middleName']) ? $owner['middleName'] : '') . '|' .
                $this->formatToRuDate($owner['birthdate']);
        }
        $data['request']['CalcRequestValue']['CalcKBMRequest']['PhysicalPersons']['PhysicalPerson'][] = $pSubject;
        return $data;
    }

}
