<?php


namespace App\Services\Company\Soglasie;

use App\Contracts\Company\Soglasie\SoglasieScoringServiceContract;
use App\Contracts\Repositories\Services\CountryServiceContract;
use App\Contracts\Repositories\Services\DocTypeServiceContract;
use App\Contracts\Repositories\Services\GenderServiceContract;
use App\Contracts\Repositories\Services\IntermediateDataServiceContract;
use App\Contracts\Repositories\Services\RequestProcessServiceContract;
use App\Contracts\Services\PolicyServiceContract;
use App\Exceptions\ApiRequestsException;
use App\Exceptions\ConmfigurationException;
use App\Services\Repositories\AddressTypeService;
use App\Traits\DateFormatTrait;
use App\Traits\TransformBooleanTrait;

class SoglasieScoringService extends SoglasieService implements SoglasieScoringServiceContract
{
    use TransformBooleanTrait, DateFormatTrait;

    protected $docTypeService;
    protected $genderService;
    protected $countryService;
    protected $addressTypeService;

    public function __construct(
        IntermediateDataServiceContract $intermediateDataService,
        RequestProcessServiceContract $requestProcessService,
        PolicyServiceContract $policyService,
	    DocTypeServiceContract $docTypeService,
        GenderServiceContract $genderService,
        CountryServiceContract $countryService,
        AddressTypeService $addressTypeService
    )
    {
        $this->docTypeService = $docTypeService;
        $this->genderService = $genderService;
        $this->countryService = $countryService;
        $this->addressTypeService = $addressTypeService;
        $this->apiWsdlUrl = config('api_sk.soglasie.scoringWsdlUrl');
        if (!($this->apiWsdlUrl)) {
            throw new ConmfigurationException('Ошибка конфигурации API ' . static::companyCode);
        }
        parent::__construct($intermediateDataService, $requestProcessService, $policyService);
    }

    public function run($company, $attributes, $token = false): array
    {
        $data = $this->prepareData($company, $attributes);
        $headers = $this->getHeaders();
        $auth = $this->getAuth();
        $xmlAttributes = [
            'request' => [
                'test' => $this->transformAnyToBoolean($this->apiIsTest),
                'partial' => $this->transformAnyToBoolean(false),
            ],
        ];

        $requestLogData = [
            'url' => $this->apiWsdlUrl,
            'payload' => $data
        ];

        $this->writeRequestLog($requestLogData);

        $response = $this->requestBySoap($this->apiWsdlUrl, 'getScoringId', $data, $auth, $headers, $xmlAttributes);

        $this->writeResponseLog($response);

        if ($token !== false) {
            $this->writeDatabaseLog(
                $token,
                $requestLogData,
                $response,
                config('api_sk.logMicroserviceCode'),
                static::companyCode,
                $this->getName(__CLASS__)
            );
        }

        if (isset($response['fault']) && $response['fault']) {
            throw new ApiRequestsException(
                'API страховой компании вернуло ошибку: ' .
                isset($response['message']) ? $response['message'] : ''
            );
        }
        if (
            !isset($response['response']->response->scoringid) ||
            !$response['response']->response->scoringid
        ) {
            throw new ApiRequestsException([
                'API страховой компании не вернуло данных',
                isset($response['response']->response->ErrorList->ErrorInfo->Message) ?
                    $response['response']->response->ErrorList->ErrorInfo->Message :
                    'нет данных об ошибке',
            ]);
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

    protected function prepareData($company, $attributes)
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
                "sex" => $this->genderService->getCompanyGender($owner['gender'], $company->id),
                "note" => '',
            ];
            foreach ($owner['documents'] as $iDocument => $document) {
                $pDocument = [
                    'doctype' => $this->docTypeService->getCompanyDocTypeByRelation2($document['document']['documentType'], $document['document']['isRussian'], $company->id),
                    'docseria' => isset($document['document']['series']) ? $document['document']['series'] : '',
                ];
                $this->setValuesByArray($pDocument, [
                    "docnumber" => 'number',
                    "docplace" => 'issuedBy',
                    "docdatebegin" => 'dateIssue',
                ], $document['document']);
                if (!isset($pDocument['docplace'])) {
                    $pDocument['docplace'] = '';
                }
                $data['request']['private']['documents']['document'][] = $pDocument;
            }
            foreach ($owner['addresses'] as $iAddress => $address) {
                $pAddress = [
                    'type' => $this->addressTypeService->getCompanyAddressType($address['address']['addressType'], $company->code),
                    'address' => $this->countryService->getCountryById($address['address']['country'])['name'] . ', ' .
                        (isset($address['address']['region']) ? $address['address']['region'] . ', ' : '') .
                        (isset($address['address']['district']) ? $address['address']['district'] . ', ' : '') .
                        (isset($address['address']['city']) ? $address['address']['city'] : $address['address']['populatedCenter']) . ', ' .
                        $address['address']['street'] . ', ' .
                        (isset($address['address']['building']) ? $address['address']['building'] . ', ' : '') .
                        (isset($address['address']['flat']) ? $address['address']['flat'] : ''),
                    'city' => isset($address['address']['city']) ? $address['address']['city'] : $address['address']['populatedCenter'],
                    'street' => $address['address']['street'],
                ];
                $this->setValuesByArray($pAddress, [
                    "house" => 'building',
                    "flat" => 'flat',
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
