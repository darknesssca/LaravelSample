<?php


namespace App\Services\Company\Renessans;


use App\Contracts\Company\Renessans\RenessansCalculateServiceContract;
use App\Contracts\Repositories\Services\CarCategoryServiceContract;
use App\Contracts\Repositories\Services\CarMarkServiceContract;
use App\Contracts\Repositories\Services\CarModelServiceContract;
use App\Contracts\Repositories\Services\DocTypeServiceContract;
use App\Contracts\Repositories\Services\IntermediateDataServiceContract;
use App\Contracts\Repositories\Services\RequestProcessServiceContract;
use App\Contracts\Repositories\Services\UsageTargetServiceContract;
use App\Contracts\Services\PolicyServiceContract;
use App\Exceptions\ApiRequestsException;
use App\Traits\TransformBooleanTrait;

class RenessansCalculateService extends RenessansService implements RenessansCalculateServiceContract
{
    use TransformBooleanTrait;

    protected $apiPath = '/calculate/?fullInformation=true';
    protected $usageTargetService;
    protected $carModelService;
    protected $docTypeService;
    protected $carMarkService;
    protected $carCategoryService;

    public function __construct(
        IntermediateDataServiceContract $intermediateDataService,
        RequestProcessServiceContract $requestProcessService,
        PolicyServiceContract $policyService,
        UsageTargetServiceContract $usageTargetService,
        CarModelServiceContract $carModelService,
        DocTypeServiceContract $docTypeService,
        CarMarkServiceContract $carMarkService,
        CarCategoryServiceContract $carCategoryService
    )
    {
        $this->usageTargetService = $usageTargetService;
        $this->carModelService = $carModelService;
        $this->docTypeService = $docTypeService;
        $this->carMarkService = $carMarkService;
        $this->carCategoryService = $carCategoryService;
        $this->init();
        parent::__construct($intermediateDataService, $requestProcessService, $policyService);
    }

    public function run($company, $attributes): array
    {
        $this->setAuth($attributes);
        $url = $this->getUrl();
        $data = $this->prepareData($company, $attributes);

        $this->writeRequestLog([
            'url' => $url,
            'payload' => $data
        ]);

        $response = $this->postRequest($url, $data, [], false);

        $this->writeResponseLog($response);

        if (!$response) {
            throw new ApiRequestsException('API страховой компании не вернуло ответ');
        }
        if (!isset($response['result']) || !$response['result']) {
            throw new ApiRequestsException([
                'API страховой компании вернуло ошибку: ',
                isset($response['message']) ? $response['message'] : ''
            ]);
        }
        if (!isset($response['data']) || !$response['data'] || !count($response['data'])) {
            throw new ApiRequestsException([
                'API страховой компании не вернуло данных',
                isset($response['message']) ? $response['message'] : 'нет данных об ошибке'
            ]);
        }
        $calcData = array_shift($response['data']);
        return [
            'calcId' => $calcData['id'],
            'premium' => false,
        ];
    }

    protected function prepareData($company, $attributes)
    {
        /**
         * todo Добавить последний параметр false, когда ренес запустят новые справочники и АИС 2.0
         */
        $carModel = $this->carModelService->getCompanyModelByName(
            $attributes['car']['maker'],
            $attributes['car']['category'],
            $attributes['car']['model'],
            $company->id,
            false
        );
        $data = [
            'key' => $attributes['key'],
            'dateStart' => $attributes['policy']['beginDate'],
            'period' => 12,
            'purpose' => $this->usageTargetService->getCompanyUsageTarget($attributes['car']['vehicleUsage'], $company->id),
            'limitDrivers' => $this->transformBooleanToInteger(!$attributes['policy']['isMultidrive']),
            'trailer' => $this->transformBooleanToInteger($attributes['car']['isUsedWithTrailer']),
            'isJuridical' => 0,
            'owner' => [
                'document' => []
            ],
            'car' => [
                'make' => $this->carMarkService->getCompanyMark($attributes['car']['maker'], $company->id),
                'model' => $carModel['model'] ? $carModel['model'] : $carModel['otherModel'],
                'MarkAndModelString' => $this->carMarkService->getCompanyMark($attributes['car']['maker'], $company->id) .
                    ' ' . $attributes['car']['model'],
                'category' => $carModel['category'],
                'power' => $attributes['car']['enginePower'],
                'documents' => [
                    'vin' => $attributes['car']['vin'],
                ],
            ],
            'usagePeriod' => [
                [
                    'dateStart' => $attributes['policy']['beginDate'],
                    'dateEnd' => $attributes['policy']['endDate'],
                ]
            ],
        ];
        //owner
        $owner = $this->searchSubjectById($attributes, $attributes['policy']['ownerId']);
        $this->setValuesByArray($data['owner'], [
            'lastname' => 'lastName',
            'name' => 'firstName',
            'birthday' => 'birthdate',
            'middlename' => 'middleName',
        ], $owner);
        $ownerAddress = $this->searchAddressByType($owner, 'registration');
        if (isset($ownerAddress['regionKladr'])) {
            $data['codeKladr'] = $ownerAddress['regionKladr'];
        }
        $ownerPassport = $this->searchDocumentByType($owner, 'passport');
        $data['owner']['document'] = [
            'typeofdocument' => $this->docTypeService->getCompanyPassportDocType($ownerPassport['isRussian'], $company->id),
        ];
        $this->setValuesByArray($data['owner']['document'], [
            "series" => 'series',
            "number" => 'number',
            "issued" => 'issuedBy',
            "dateIssue" => 'dateIssue',
        ], $ownerPassport);
        //car
        $category = $this->carCategoryService->getCategoryById($attributes['car']['category']);
        if (strtolower($category['code']) == 'c') {
            $this->setValuesByArray($data['car'], [
                "UnladenMass" => 'minWeight',
                "ResolutionMaxWeight" => 'maxWeight',
            ], $attributes['car']);
        } elseif (strtolower($category['code']) == 'd') {
            $this->setValuesByArray($data['car'], [
                "NumberOfSeats" => 'seats',
            ], $attributes['car']);
        }
        $this->setValuesByArray($data['car']['documents'], [
            "registrationNumber" => 'regNumber',
        ], $attributes['car']);
        //drivers
        if (!$attributes['policy']['isMultidrive']) {
            $data['drivers'] = [];
            $drivers = $this->searchDrivers($attributes);
            foreach ($drivers as $iDriver => $driver) {
                $pDriver = [];
                $this->setValuesByArray($pDriver, [
                    'lastname' => 'lastName',
                    'name' => 'firstName',
                    'birthday' => 'birthdate',
                    'middlename' => 'middleName',
                ], $driver);
                $pDriver['license'] = [
                    'dateBeginDrive' => $driver['dateBeginDrive'],
                ];
                $driverLicense = $this->searchDocumentByType($driver, 'license');
                if ($driverLicense) {
                    $this->setValuesByArray($pDriver['license'], [
                        "series" => 'series',
                        "number" => 'number',
                        "dateIssue" => 'dateIssue',
                    ], $driverLicense);
                }
                $data['drivers'][] = $pDriver;
            }
        }
        return $data;
    }

}
