<?php


namespace App\Services\Company\Renessans;


use App\Contracts\Company\Renessans\RenessansCreateServiceContract;
use App\Contracts\Repositories\PolicyRepositoryContract;
use App\Contracts\Repositories\Services\CarMarkServiceContract;
use App\Contracts\Repositories\Services\DocTypeServiceContract;
use App\Contracts\Repositories\Services\IntermediateDataServiceContract;
use App\Contracts\Repositories\Services\RequestProcessServiceContract;
use App\Contracts\Repositories\Services\UsageTargetServiceContract;
use App\Exceptions\ApiRequestsException;
use App\Traits\TransformBooleanTrait;

class RenessansCreateService extends RenessansService implements RenessansCreateServiceContract
{
    use TransformBooleanTrait;

    protected $apiPath = '/create/';

    public function __construct(
        IntermediateDataServiceContract $intermediateDataService,
        RequestProcessServiceContract $requestProcessService,
        PolicyRepositoryContract $policyRepository
    )
    {
        $this->init();
        parent::__construct($intermediateDataService, $requestProcessService, $policyRepository);
    }

    protected function setAdditionalFields(&$attributes) {
        $attributes['CheckSegment'] = intval(isset($attributes['CheckSegment']) && $attributes['CheckSegment']);
    }

    public function run($company, $attributes): array
    {
        $this->setAdditionalFields($attributes);
        $this->setAuth($attributes);
        $url = $this->getUrl();
        $data = $this->prepareData($company, $attributes);
        $response = $this->postRequest($url, $data, [], false);
        if (!$response) {
            throw new ApiRequestsException('API страховой компании не вернуло ответ');
        }
        if (!$response['result']) {
            throw new ApiRequestsException(
                'API страховой компании вернуло ошибку: ' .
                isset($response['message']) ? $response['message'] : ''
            );
        }
        if (!isset($response['data']['policyId']) || !$response['data']['policyId']) {
            throw new ApiRequestsException([
                'API страховой компании не вернуло номер созданного полиса',
                isset($response['message']) ? $response['message'] : 'нет данных об ошибке'
            ]);
        }
        return [
            'policyId' => $response['data']['policyId'],
        ];
    }

    protected function prepareData($company, $attributes): array
    {
        $usageTargetService = app(UsageTargetServiceContract::class);
        $carMarkService = app(CarMarkServiceContract::class);
        $docTypeService = app(DocTypeServiceContract::class);
        $insurer = $this->searchSubjectById($attributes, $attributes['policy']['insurantId']);
        $owner = $this->searchSubjectById($attributes, $attributes['policy']['ownerId']);
        $data = [
            'key' => $attributes['key'],
            'CheckSegment' => $this->transformBooleanToInteger($attributes['CheckSegment']),
            'calculationId' => $attributes['calcId'],
            'purpose' => $usageTargetService->getCompanyUsageTarget($attributes['car']['vehicleUsage'], $company->id),
            'cabinet' => [
                'email' => $insurer['email'],
            ],
            'isInsurerJuridical' => $this->transformBooleanToInteger(false),
            'car' => [
                'year' => $attributes['car']['year'],
                'MarkAndModelString' =>  $carMarkService->getCarMarkName($attributes['car']['maker']) .
                    ' ' . $attributes['car']['model'],
            ],
        ];
        if ($attributes['car']['document']['documentType'] == 'pts') {
            $data['car']['pts'] = [];
            $this->setValuesByArray($data['car']['pts'], [
                'serie' => 'series',
                'number' => 'number',
                'dateIssue' => 'dateIssue',
            ], $attributes['car']['document']);
        } else {
            $data['car']['sts'] = [
                'docType' => $docTypeService->getCompanyCarDocType($attributes['car']['document']['documentType'], $company->id)
            ];
            $this->setValuesByArray($data['car']['sts'], [
                'serie' => 'series',
                'number' => 'number',
                'dateIssue' => 'dateIssue',
            ], $attributes['car']['document']);
        }
        $data['car']['diagnostic'] = [];
        $this->setValuesByArray($data['car']['diagnostic'], [
            'number' => 'number',
            'validDate' => 'dateEnd',
        ], $attributes['car']['inspection']);
        $data['insurer'] = $this->getSubjectData($company, $insurer);
        $data['owner'] = $this->getSubjectData($company, $owner);
        return $data;
    }

    protected function getSubjectData($company, $subject)
    {
        $docTypeService = app(DocTypeServiceContract::class);
        $subjectData = [];
        $this->setValuesByArray($subjectData, [
            'email' => 'email',
            'phone' => 'phone',
            'name' => 'firstName',
            'lastname' => 'lastName',
            'middlename' => 'middleName',
            'birthday' => 'birthdate',
        ], $subject);
        $document = $this->searchDocumentByType($subject, 'passport');
        if ($document) {
            $subjectData['document'] = [
                'typeofdocument' => $docTypeService->getCompanyPassportDocType($document['isRussian'], $company->id),
            ];
            $this->setValuesByArray($subjectData['document'], [
                'series' => 'series',
                'number' => 'number',
                'dateIssue' => 'dateIssue',
                'issued' => 'issuedBy',
                'codeDivision' => 'subdivisionCode',
            ], $document);
        }
        $regAddress = $this->searchAddressByType($subject, 'registration');
        if ($regAddress) {
            $subjectData['addressJuridical'] = $this->getAddressData($regAddress);
        }
        $factAddress = $this->searchAddressByType($subject, 'home');
        if ($factAddress) {
            $subjectData['addressFact'] = $this->getAddressData($factAddress);
        }
        return $subjectData;
    }

    protected function getAddressData($address)
    {
        $addressData = [];
        $this->setValuesByArray($addressData, [
            'country' => 'country',
            'zip' => 'postCode',
            'city' => 'city',
            'settlement' => 'populatedCenter',
            'street' => 'street',
            'home' => 'building',
            'flat' => 'flat',
            'area' => 'district',
            'region' => 'region',
            'kladr' => 'streetKladr',
        ], $address);
        return $addressData;
    }

}
