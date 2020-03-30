<?php


namespace App\Services\Company\Renessans;


use App\Contracts\Company\Renessans\RenessansCalculateServiceContract;
use App\Exceptions\ApiRequestsException;
use App\Traits\TransformBooleanTrait;

class RenessansCalculateService extends RenessansService implements RenessansCalculateServiceContract
{
    use TransformBooleanTrait;

    protected $apiPath = '/calculate/?fullInformation=true';

    public function run($company, $attributes): array
    {
        $this->init();
        $this->setAuth($attributes);
        $url = $this->getUrl();
        $data = $this->prepareData($attributes);
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

    protected function prepareData($attributes)
    {
        $data = [
            'key' => $attributes['key'],
            'dateStart' => $attributes['policy']['beginDate'],
            'period' => 12,
            'purpose' => $attributes['car']['vehicleUsage'], // todo справочник
            'limitDrivers' => $this->transformBooleanToInteger(!$attributes['policy']['isMultidrive']),
            'trailer' => $this->transformBooleanToInteger($attributes['car']['isUsedWithTrailer']),
            'isJuridical' => 0,
            'owner' => [
                'document' => []
            ],
            'car' => [
                'make' => $attributes['car']['maker'], // TODO: справочник,
                'model' => $attributes['car']['model'], // TODO: справочник,
                'MarkAndModelString' => $attributes['car']['maker'] . ' ' . $attributes['car']['model'], //todo справочник
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
        $this->setValuesByArray($data['owner'], [
            'lastname' => 'lastName',
            'name' => 'firstName',
            'birthday' => 'birthdate',
            'middlename' => 'middleName',
        ], $owner);
        $ownerAddress = $this->searchAddressByType($owner, 'registration'); // TODO: справочник
        $data['codeKladr'] = $ownerAddress['regionKladr']; // TODO проверить что за кладр
        $ownerPassport = $this->searchDocumentByType($owner, 'RussianPassport'); // TODO: справочник
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
