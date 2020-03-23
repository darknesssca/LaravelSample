<?php


namespace App\Services\Company\Renessans;


use App\Contracts\Company\Renessans\RenessansCalculateServiceContract;
use App\Http\Controllers\RestController;
use App\Models\InsuranceCompany;

class RenessansCalculateService extends RenessansService implements RenessansCalculateServiceContract
{
    protected $apiPath = '/calculate/?fullInformation=true';

    private $catalogPurpose = ["Личная", "Такси"]; // TODO: значение из справочника, справочник нужно прогружать при валидации, будет кэшироваться
    private $catalogTypeOfDocument = []; // TODO: значение из справочника, справочник нужно прогружать при валидации, будет кэшироваться
    private $catalogCatCategory = ["A", "B"]; // TODO: значение из справочника, справочник нужно прогружать при валидации, будет кэшироваться

    public function run(InsuranceCompany $company, $attributes, $additionalFields = []): array
    {
        $this->setAuth($attributes);
        $url = $this->getUrl();
        $data = $this->prepareData($attributes);
        $response = RestController::postRequest($url, $data);
        if (!$response) {
            throw new \Exception('api not return answer');
        }
        if (!$response['result']) {
            throw new \Exception('api return '.isset($response['message']) ? $response['message'] : 'no message');
        }
        $result = [
            'calculateValues' => [],
        ];
        foreach ($response['data'] as $responseData) {
            $result['calculateValues'][] = [
                'calcId' => $responseData['id'],
                'premium' => false,
            ];
        }
        return $result;
    }

    public function prepareData($attributes)
    {
        $data = [
            'key' => $attributes['key'],
            'dateStart' => $attributes['policy']['beginDate'],
            'period' => 12,
            'purpose' => $attributes['car']['vehicleUsage'], // todo справочник
            'limitDrivers' => $this->transformBooleanToInteger($attributes['policy']['isMultidrive']),
            'trailer' => $this->transformBooleanToInteger($attributes['car']['isUsedWithTrailer']),
            'isJuridical' => 0,
            'owner' => [],
            'car' => [
                'make' => $attributes['car']['maker'], // TODO: справочник,
                'model' => $attributes['car']['model'], // TODO: справочник,
                //'MarkAndModelString' => [], //todo после реализации справочников, при отсутствии модели или марки в справочнике указывать строку
                'category' => 'B', // TODO: справочник,
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
        $data['owner'] = [
            "lastname" => $owner['lastName'],
            "name" => $owner['firstName'],
            //"middlename" => $owner['middleName'],
            "birthday" => $owner['birthdate'],
            'document' => [],
        ];
        $this->setValue($data['owner'], 'middlename', 'middleName', $owner);
        $ownerAddress = $this->searchAddressByType($owner, 'registration');
        $data['codeKladr'] = $ownerAddress['regionKladr'];
        $ownerPassport = $this->searchDocumentByType($owner, 'RussianPassport');
        $data['owner']['document'] = [
            'typeofdocument' => $ownerPassport['documentType'],  // TODO: справочник
        ];
        $this->setValuesByArray($data['owner']['document'], [
            "series" => 'series',
            "number" => 'number',
            "issued" => 'issuedBy',
            "dateIssue" => 'dateIssue',
        ], $ownerPassport);
        //car
        $this->setValuesByArray($data['car'], [
            "UnladenMass" => 'minWeight',
            "ResolutionMaxWeight" => 'maxWeight',
            "NumberOfSeats" => 'seats',
        ], $attributes['car']);
        //drivers
        if (!$attributes['policy']['isMultidrive']) {
            $data['drivers'] = [];
            $drivers = $this->searchDrivers($attributes);
            foreach ($drivers as $iDriver => $driver) {
                $pDriver = [
                    'name' => $owner['firstName'],
                    'lastname' => $owner['firstName'],
                    'birthday' => $owner['birthdate'],
                ];
                foreach ($data['drivers'] as $tDriver) {
                    if ($iDriver == $tDriver['driver']['driverId']) {
                        $pDriver['license'] = [
                            "dateBeginDrive" => $tDriver['driver']['drivingLicenseIssueDateOriginal'],
                        ];
                    }
                }
                $this->setValue($pDriver, 'middlename', 'middleName', $driver);
                $driverLicense = $this->searchDocumentByType($driver, 'DriverLicense'); // todo значение из справочника
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
