<?php


namespace App\Services\Company\Soglasie;

use App\Contracts\Company\Soglasie\SoglasieScoringServiceContract;
use App\Contracts\Repositories\IntermediateDataRepositoryContract;
use App\Contracts\Repositories\PolicyRepositoryContract;
use App\Contracts\Repositories\RequestProcessRepositoryContract;
use App\Exceptions\ApiRequestsException;
use App\Exceptions\ConmfigurationException;
use App\Traits\DateFormat;
use App\Traits\TransformBoolean;

class SoglasieScoringService extends SoglasieService implements SoglasieScoringServiceContract
{
    use TransformBoolean, DateFormat;

    public function __construct(
        IntermediateDataRepositoryContract $intermediateDataRepository,
        RequestProcessRepositoryContract $requestProcessRepository,
        PolicyRepositoryContract $policyRepository
    )
    {
        $this->apiWsdlUrl = config('api_sk.soglasie.scoringWsdlUrl');
        if (!($this->apiWsdlUrl)) {
            throw new ConmfigurationException('Ошибка конфигурации API ' . static::companyCode);
        }
        parent::__construct($intermediateDataRepository, $requestProcessRepository, $policyRepository);
    }

    public function run($company, $attributes): array
    {
        $data = $this->prepareData($attributes);
        $headers = $this->getHeaders();
        $auth = $this->getAuth();
        $xmlAttributes = [
            'request' => [
                'test' => $this->transformAnyToBoolean($this->apiIsTest),
                'partial' => $this->transformAnyToBoolean(false),
            ],
        ];
        $response = $this->requestBySoap($this->apiWsdlUrl, 'getScoringId', $data, $auth, $headers, $xmlAttributes);
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

    protected function prepareData($attributes)
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
                "sex" => $owner['gender'], // todo из справочника
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
