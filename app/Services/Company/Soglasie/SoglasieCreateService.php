<?php


namespace App\Services\Company\Soglasie;

use App\Contracts\Company\Soglasie\SoglasieCreateServiceContract;
use App\Exceptions\ConmfigurationException;
use App\Traits\DateFormat;
use App\Traits\TransformBoolean;

class SoglasieCreateService extends SoglasieService implements SoglasieCreateServiceContract
{
    use TransformBoolean, DateFormat;

    public function __construct()
    {
        $this->apiRestUrl = config('api_sk.soglasie.createUrl');
        if (!($this->apiRestUrl)) {
            throw new ConmfigurationException('Ошибка конфигурации API ' . static::companyCode);
        }
        parent::__construct();
    }

    public function run($company, $attributes): array
    {
        $data = $this->prepareData($attributes);
        $headers = $this->getHeaders();
        $response = $this->postRequest($this->apiRestUrl, $data, $headers);
        return $response; // todo сделать адекватный вывод параметров
    }

    protected function getHeaders()
    {
        return [
            'Authorization' => 'Basic ' . base64_encode($this->apiUser . ':' . $this->apiSubUser . ':' . $this->apiSubPassword),
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
        ];
    }

    public function prepareData($attributes)
    {
        $owner = $this->searchSubjectById($attributes, $attributes['policy']['ownerId']);
        $insurer = $this->searchSubjectById($attributes, $attributes['policy']['insurantId']);
        $data = [
            'CodeInsurant' => '000',
            'BeginDate' => $this->dateTimeFromDate($attributes['policy']['beginDate']),
            'EndDate' => $this->dateTimeFromDate($attributes['policy']['endDate']),
            //'PrevPolicy' => '', //todo пролонгация
            'Period1Begin' => $attributes['policy']['beginDate'],
            'Period1End' => $attributes['policy']['endDate'],
            'IsTransCar' => false, // fixme заглушка
            'IsInsureTrailer' => $this->transformAnyToBoolean($attributes['car']['isUsedWithTrailer']),
            'CarInfo' => [
                'VIN' => $attributes['car']['vin'],
                'MarkModelCarCode' => $attributes['car']['model'],
                'MarkPTS' => 'NISSAN',//$attributes['car']['mark'], // todo справочник
                'ModelPTS' => $attributes['car']['model'], // todo справочник
                'YearIssue' => $attributes['car']['year'],
                'DocumentCar' => [],
                'TicketCar' => [
                    'TypeRSA' => $attributes['car']['inspection']['documentType'],
                    'Number' => $attributes['car']['inspection']['number'],
                    'Date' => $attributes['car']['inspection']['dateIssue'],
                ],
                'TicketCarYear' => $this->getYearFromDate($attributes['car']['inspection']['dateEnd']),
                'TicketCarMonth' => $this->getMonthFromDate($attributes['car']['inspection']['dateEnd']),
                'TicketDiagnosticDate' => $attributes['car']['inspection']['dateIssue'],
                'EngCap' => $attributes['car']['enginePower'],
                'GoalUse' => "Personal", //$attributes['car']['vehicleUsage'], // todo справочник
                'Rented' => false, // todo зависит от предыдущего справочника
            ],
            'Insurer' => [
                'Phisical' => [
                    'Resident' => $insurer['isResident'],
                    'Surname' => $insurer['lastName'],
                    'Name' => $insurer['firstName'],
                    'BirthDate' => $insurer['birthdate'],
                    'Sex' => $insurer['gender'],
                    'Email' => $insurer['email'],
                    'PhoneMobile' => $insurer['phone']['numberPhone'],
                    'Documents' => [
                        'Document' => [],
                    ],
                    'Addresses' => [
                        'Address' => [],
                    ],
                ],
            ],
            'CarOwner' => [
                'Phisical' => [
                    'Resident' => $owner['isResident'],
                    'Surname' => $owner['lastName'],
                    'Name' => $owner['firstName'],
                    'BirthDate' => $owner['birthdate'],
                    'Sex' => $owner['gender'],
                    'Email' => $owner['email'],
                    'PhoneMobile' => $owner['phone']['numberPhone'],
                    'Documents' => [
                        'Document' => [],
                    ],
                    'Addresses' => [
                        'Address' => [],
                    ],
                ],
            ],
            'Drivers' => [
                'Driver' => [],
            ],
            'IKP1l' => ' ',
        ];
        $this->setValuesByArray($data['CarInfo'], [
            "MaxMass" => 'maxWeight',
            "PasQuant" => 'seats',
        ], $attributes['car']);
        $this->setValuesByArray($data['CarInfo']['TicketCar'], [
            "Serial" => 'documentSeries',
        ], $attributes['car']['inspection']);
        //car.documents
        $data['CarInfo']['DocumentCar'] = [
            'TypeRSA' => $attributes['car']['document']['documentType'],
            'IsPrimary' => true,
        ];
        $this->setValuesByArray($data['CarInfo']['DocumentCar'], [
            "Serial" => 'series',
            "Number" => 'number',
            "Date" => 'dateIssue',
        ], $attributes['car']['document']);
        //owner
        $this->setValuesByArray($data['CarOwner']['Phisical'], [
            "Patronymic" => 'middleName',
        ], $owner);
        $data['CarOwner']['Phisical']['Documents']["Document"] = $this->prepareSubjectDocument($owner);
        $data['CarOwner']['Phisical']['Addresses']["Address"] = $this->prepareSubjectAddress($owner);
        //insurer
        $data['Insurer']['Phisical']['Documents']["Document"] = $this->prepareSubjectDocument($insurer);
        $data['Insurer']['Phisical']['Addresses']["Address"] = $this->prepareSubjectAddress($insurer);
        // drivers
        if (count($attributes['drivers'])) {
            $data['Drivers'] = [];
            foreach ($attributes['drivers'] as $driverRef) {
                $driver = $this->searchSubjectById($attributes, $driverRef['driver']['driverId']);
                $data['Drivers'][] = $this->prepareDriver($driver, $driverRef);
            }
        } else {
            $data['Insurer']['Phisical']['Addresses'] = [];
            $driverRef = array_shift($attributes['drivers']);
            $driver = $this->searchSubjectById($attributes, $driverRef['driver']['driverId']);
            $data['Driver'] = $this->prepareDriver($driver, $driverRef);
        }
        return $data;
    }

    protected function prepareSubjectDocument($subject)
    {
        $documents = [];
        foreach ($subject['documents'] as $document) {
            $pDocument = [
                'TypeRSA' => $document['document']['documentType'],
                'Number' => $document['document']['number'],
                'Date' => $document['document']['dateIssue'],
                'Exit' => $document['document']['issuedBy'],
                'IsPrimary' => $document['document']['documentType'] == 'passport' ? true : false, // todo из справочника
            ];
            $this->setValuesByArray($pDocument, [
                "Serial" => 'series',
            ], $document['document']);
            $documents[] = $pDocument;
        }
        return $documents;
    }

    protected function prepareDriverDocument($subject)
    {
        $documents = [];
        foreach ($subject['documents'] as $document) {
            $pDocument = [
                'TypeRSA' => $document['document']['documentType'],
                'Number' => $document['document']['number'],
            ];
            $this->setValuesByArray($pDocument, [
                "Serial" => 'series',
            ], $document['document']);
            $documents[] = $pDocument;
        }
        return $documents;
    }

    protected function prepareSubjectAddress($subject)
    {
        $addresses = [];
        foreach ($subject['addresses'] as $address) {
            $pAddress = [
                'Type' => $address['address']['addressType'],
                'Country' => '643',//$address['address']['country'], // todo из справочника
                'AddressCode' => $address['address']['streetKladr'],
            ];
            $this->setValuesByArray($pAddress, [
                'Street' => 'street',
                'Hous' => 'building',
                'Flat' => 'flat',
                'Index' => 'postCode',
            ], $address['address']);
            if (!$subject['isResident']) {
                $pAddress['AddressString'] = isset($address['address']['region']) ? $address['address']['region'] . ', ' : '' .
                isset($address['address']['district']) ? $address['address']['district'] . ', ' : '' .
                isset($address['address']['city']) ? $address['address']['city'] . ', ' : '' .
                isset($address['address']['populatedCenter']) ? $address['address']['populatedCenter'] . ', ' : '' .
                isset($address['address']['street']) ? $address['address']['street'] . ', ' : '' .
                isset($address['address']['building']) ? $address['address']['building'] . ', ' : '' .
                isset($address['address']['flat']) ? $address['address']['flat'] . ', ' : '';
            }
            $addresses[] = $pAddress;
        }
        return $addresses;
    }

    protected function prepareDriver($driver, $driverRef)
    {
        $pDriver = [
            'Face' => [
                'Resident' => $driver['isResident'],
                'Surname' => $driver['lastName'],
                'Name' => $driver['firstName'],
                'BirthDate' => $driver['birthdate'],
                'Sex' => $driver['gender'],
                'Documents' => [
                    'Document' => [],
                ],
                'Addresses' => [
                    'Address' => [],
                ],
            ],
            'DrivingExpDate' => $driverRef['driver']['drivingLicenseIssueDateOriginal'],
        ];
        $this->setValuesByArray($pDriver['Face'], [
            'Patronymic' => 'middleName',
        ], $driver);
        $pDriver['Face']['Documents']['Document'] = $this->prepareDriverDocument($driver);
        $pDriver['Face']['Addresses']['Address'] = $this->prepareSubjectAddress($driver);
        return $pDriver;
    }

}
