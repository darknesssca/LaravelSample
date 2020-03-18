<?php


namespace App\Services\Company\Soglasie;

use App\Contracts\Company\Soglasie\SoglasieCreateServiceContract;
use App\Http\Controllers\RestController;
use App\Models\InsuranceCompany;
use App\Models\IntermediateData;
use Illuminate\Support\Carbon;

class SoglasieCreateService extends SoglasieService implements SoglasieCreateServiceContract
{

    private $catalogPurpose = ["Личная", "Такси"]; // TODO: значение из справочника, справочник нужно прогружать при валидации, будет кэшироваться
    private $catalogTypeOfDocument = []; // TODO: значение из справочника, справочник нужно прогружать при валидации, будет кэшироваться
    private $catalogCatCategory = ["A", "B"]; // TODO: значение из справочника, справочник нужно прогружать при валидации, будет кэшироваться

    public function __construct()
    {
        $this->apiRestUrl = config('api_sk.soglasie.createUrl');
        if (!($this->apiRestUrl)) {
            throw new \Exception('soglasie api is not configured');
        }
        parent::__construct();
    }

    public function run($company, $attributes, $additionalFields = []): array
    {
        return $this->sendCreate($company, $attributes);
    }

    private function sendCreate($company, $attributes): array
    {
        $data = $this->prepareData($attributes);
        $headers = $this->getHeaders();
        return $data;
        $response = RestController::postRequest($this->apiRestUrl, $data, $headers);
        return $response;
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
        if (!$owner) {
            throw new \Exception('no owner');
        }
        $data = [
            'CodeInsurant' => '000',
            'BeginDate' => $this->formatDateTime($attributes['policy']['beginDate']),
            'EndDate' => $this->formatDateTime($attributes['policy']['endDate']),
            //'PrevPolicy' => '', //todo пролонгация
            'Period1Begin' => $attributes['policy']['beginDate'],
            'Period1End' => $attributes['policy']['endDate'],
            'IsTransCar' => false, // fixme заглушка
            'IsInsureTrailer' => $this->transformBoolean($attributes['car']['isUsedWithTrailer']),
            'CarInfo' => [
                'VIN' => $attributes['car']['vin'],
                'MarkModelCarCode' => $attributes['car']['model'],
                'MarkPTS' => 'NISSAN',//$attributes['car']['mark'], // todo справочник
                'ModelPTS' => $attributes['car']['model'], // todo справочник
                'YearIssue' => $attributes['car']['year'],
                'DocumentCar' => [],
                'TicketCar' => [
                    'TypeRSA' => $attributes['car']['inspection']['documentType'],
                    'Number' => $attributes['car']['inspection']['documentNumber'],
                    'Date' => $attributes['car']['inspection']['documentIssuedDate'],
                ],
                'TicketCarYear' => Carbon::createFromFormat('Y-m-d', $attributes['car']['inspection']['documentDateEmd'])->format('Y'),
                'TicketCarMonth' => Carbon::createFromFormat('Y-m-d', $attributes['car']['inspection']['documentDateEmd'])->format('m'),
                'TicketDiagnosticDate' => $attributes['car']['inspection']['documentIssuedDate'],
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
        $carDocument = $this->searchDocumentByType($attributes['car'], 'PTS'); // todo справочник
        if (!$carDocument) {
            $carDocument = $this->searchDocumentByType($attributes['car'], '31'); // todo справочник
            if (!$carDocument) {
                throw new \Exception('no pts and sts');
            }
        }
        $data['CarInfo']['DocumentCar'] = [
            'TypeRSA' => $carDocument['documentType'],
            'Number' => $carDocument['documentNumber'],
            'Date' => $carDocument['documentIssued'],
            'IsPrimary' => true,
        ];
        $this->setValuesByArray($data['CarInfo']['DocumentCar'], [
            "Serial" => 'documentSeries',
        ], $carDocument);
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

//    protected function prepareDriver($driver, $driverRef)
//    {
//        $driverDocument = $this->searchDocumentByType($driver, 'driverLicense'); // todo справочник
//        $pDriver = [
//            'Face' => [
//                'Resident' => $driver['isResident'],
//                'Surname' => $driver['lastName'],
//                'Name' => $driver['firstName'],
//                'BirthDate' => $driver['birthdate'],
//                'Sex' => $driver['gender'],
//                'Document' => [
//                    'TypeRSA' => $driverDocument['documentType'],
//                    'Number' => $driverDocument['number'],
//                    'Date' => $driverDocument['dateIssue'],
//                ],
//            ],
//            'DrivingExpDate' => $driverRef['driver']['drivingLicenseIssueDateOriginal'],
//        ];
//        $this->setValuesByArray($pDriver['Face'], [
//            'Patronymic' => 'middleName',
//        ], $driver);
//        $this->setValuesByArray($pDriver['Face']['Document'], [
//            "Serial" => 'series',
//        ], $driverDocument);
//        if (count($driver['addresses']) > 1) {
//            $pDriver['Face']['Addresses'] = [];
//            foreach ($driver['addresses'] as $address) {
//                $pAddress = [
//                    'Type' => $address['address']['addressType'],
//                    'Country' => $address['address']['country'], // todo из справочника
//                    'AddressCode' => $address['address']['streetKladr'],
//                ];
//                $this->setValuesByArray($pAddress, [
//                    'Street' => 'street',
//                    'Hous' => 'building',
//                    'Flat' => 'flat',
//                    'Index' => 'postCode',
//                ], $address['address']);
//                if (!$driver['isResident']) {
//                    $pAddress['AddressString'] = isset($address['address']['region']) ? $address['address']['region'] . ', ' : '' .
//                    isset($address['address']['district']) ? $address['address']['district'] . ', ' : '' .
//                    isset($address['address']['city']) ? $address['address']['city'] . ', ' : '' .
//                    isset($address['address']['populatedCenter']) ? $address['address']['populatedCenter'] . ', ' : '' .
//                    isset($address['address']['street']) ? $address['address']['street'] . ', ' : '' .
//                    isset($address['address']['building']) ? $address['address']['building'] . ', ' : '' .
//                    isset($address['address']['flat']) ? $address['address']['flat'] . ', ' : '';
//                }
//                $pDriver['Face']['Addresses'][] = $pAddress;
//            }
//        } else {
//            $address = array_shift($driver['addresses']);
//            $pDriver['Face']['Address'] = [
//                'Type' => $address['address']['addressType'],
//                'Country' => $address['address']['country'], // todo из справочника
//                'AddressCode' => $address['address']['streetKladr'],
//            ];
//            $this->setValuesByArray($pDriver['Face']['Address'], [
//                'Street' => 'street',
//                'Hous' => 'building',
//                'Flat' => 'flat',
//                'Index' => 'postCode',
//            ], $address);
//            if (!$driver['isResident']) {
//                $data['Insurer']['Phisical']['Address']['AddressString'] = isset($address['address']['region']) ? $address['address']['region'] . ', ' : '' .
//                isset($address['address']['district']) ? $address['address']['district'] . ', ' : '' .
//                isset($address['address']['city']) ? $address['address']['city'] . ', ' : '' .
//                isset($address['address']['populatedCenter']) ? $address['address']['populatedCenter'] . ', ' : '' .
//                isset($address['address']['street']) ? $address['address']['street'] . ', ' : '' .
//                isset($address['address']['building']) ? $address['address']['building'] . ', ' : '' .
//                isset($address['address']['flat']) ? $address['address']['flat'] . ', ' : '';
//            }
//        }
//        return $pDriver;
//    }

}
