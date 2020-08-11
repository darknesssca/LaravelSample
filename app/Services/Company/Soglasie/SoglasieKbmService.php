<?php


namespace App\Services\Company\Soglasie;

use App\Contracts\Company\Soglasie\SoglasieKbmServiceContract;
use App\Contracts\Repositories\Services\DocTypeServiceContract;
use App\Contracts\Repositories\Services\IntermediateDataServiceContract;
use App\Contracts\Repositories\Services\RequestProcessServiceContract;
use App\Contracts\Services\PolicyServiceContract;
use App\Exceptions\ApiRequestsException;
use App\Exceptions\ConmfigurationException;
use App\Traits\DateFormatTrait;
use App\Traits\TransformBooleanTrait;

class SoglasieKbmService extends SoglasieService implements SoglasieKbmServiceContract
{
    use TransformBooleanTrait, DateFormatTrait;

    public function __construct(
        IntermediateDataServiceContract $intermediateDataService,
        RequestProcessServiceContract $requestProcessService,
        PolicyServiceContract $policyService
    )
    {
        $this->apiWsdlUrl = config('api_sk.soglasie.kbmWsdlUrl');
        if (!$this->apiWsdlUrl) {
            throw new ConmfigurationException('Ошибка конфигурации API ' . static::companyCode);
        }
        parent::__construct($intermediateDataService, $requestProcessService, $policyService);
    }

    public function run($company, $attributes): array
    {
        $data = $this->prepareData($company, $attributes);
        $headers = $this->getHeaders();
        $auth = $this->getAuth();

        $this->writeRequestLog([
            'url' => $this->apiWsdlUrl,
            'payload' => $data
        ]);

        $response = $this->requestBySoap($this->apiWsdlUrl, 'getKbm', $data, $auth, $headers);

        $this->writeResponseLog($response);

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
            'kbmId' => $response['response']->response->CalcResponseValue->PolicyCalc->PolicyKBMValue,
        ];
    }

    protected function prepareData($company, $attributes)
    {
        $docTypeService = app(DocTypeServiceContract::class);
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
        $drivers = $this->searchDrivers($attributes);
        foreach ($drivers as $driver) {
            $pSubject = [];
            foreach ($driver['documents'] as $iDocument => $document) {
                $pDocument = [];
//                if ($document['document']['documentType'] == 'passport') {
//                    $pDocument['DocPerson'] = $docTypeService->getCompanyPassportDocType2($document['document']['isRussian'], $company->id);
//                }
                $this->setValuesByArray($pDocument, [
                    "Serial" => 'series',
                    "Number" => 'number',
                ], $document['document']);
                switch ($document['document']['documentType']) {
                    case 'license':
                        $pSubject['DriverDocument'] = $pDocument;
                        break;
//                    case 'passport':
//                        $pSubject['PersonDocument'] = $pDocument;
//                        break;
                    default:
                        break;
                }
                $pSubject['PersonNameBirthHash'] = '### '.
                    $driver['lastName'] . '|'.
                    $driver['firstName'] . '|'.
                    (isset($driver['middleName']) ? $driver['middleName'] : '') . '|' .
                    $this->formatToRuDate($driver['birthdate']);
            }
            $data['request']['CalcRequestValue']['CalcKBMRequest']['PhysicalPersons']['PhysicalPerson'][] = $pSubject;
        }
        return $data;
    }

}
