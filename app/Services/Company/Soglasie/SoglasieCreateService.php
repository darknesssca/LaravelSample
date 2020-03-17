<?php


namespace App\Services\Company\Soglasie;

use App\Contracts\Company\Soglasie\SoglasieCreateServiceContract;
use App\Http\Controllers\SoapController;
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
        $this->apiWsdlUrl = config('api_sk.soglasie.createUrl');
        if (!($this->apiWsdlUrl)) {
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
        //$response = SoapController::requestBySoap($this->apiWsdlUrl, 'CalcProduct', $data, $auth, $headers);
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
        if (!$owner) {
            throw new \Exception('no owner');
        }
        $data = [
            'CodeInsurant' => '000',
            'BeginDate' => $this->formatDateTime($attributes['policy']['beginDate']),
            'EndDate' => $this->formatDateTime($attributes['policy']['endDate']),
            //'PrevPolicy' => '', //todo пролонгация
            'Period1Begin' => $this->formatDateTime($attributes['policy']['beginDate']),
            'Period1End' => $this->formatDateTime($attributes['policy']['endDate']),
            'IsTransCar' => false, // fixme заглушка
            'IsInsureTrailer' => $this->transformBoolean($attributes['car']['isUsedWithTrailer']),
            'CarInfo' => [
                'VIN' => $attributes['car']['vin'],
                'MarkModelCarCode' => $attributes['car']['model'],
                'MarkPTS' => $attributes['car']['mark'], // todo справочник
                'ModelPTS' => $attributes['car']['model'], // todo справочник
                'YearIssue' => $attributes['car']['year'],
                'DocumentCar' => [],
                'TicketCar' => [
                    'TypeRSA' => $attributes['car']['docInspection']['documentType'],
                    'Number' => $attributes['car']['docInspection']['documentNumber'],
                    'Date' => $attributes['car']['docInspection']['documentIssued'],
                ],
                'TicketCarYear' => Carbon::createFromFormat('Y-m-d', $attributes['car']['docInspection']['documentDateEmd'])->format('Y'),
                'TicketCarMonth' => Carbon::createFromFormat('Y-m-d', $attributes['car']['docInspection']['documentDateEmd'])->format('m'),
                'TicketDiagnosticDate' => $attributes['car']['docInspection']['documentIssuedDate'],
                'EngCap' => $attributes['car']['enginePower'],
                'GoalUse' => $attributes['car']['vehicleUsage'], // todo справочник
                'Rented' => false, // todo зависит от предыдущего справочника
            ],
            'Insurer' => [
                'Phisical' => [],
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
                ],
            ],
            //'Drivers {Driver}' => [],
            'IKP1l' => ' ',
        ];
        $this->setValuesByArray($data['CarInfo'], [
            "MaxMass" => 'maxWeight',
            "PasQuant" => 'seats',
        ], $attributes['car']);
        $this->setValuesByArray($data['CarInfo']['TicketCar'], [
            "Serial" => 'documentSeries',
        ], $attributes['car']['docInspection']);
        //car.documents
        $carDocument = $this->searchDocumentByType($attributes['car'], 'PTS'); // todo справочник
        if (!$carDocument) {
            $carDocument = $this->searchDocumentByType($attributes['car'], 'STS'); // todo справочник
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
        //subjects
        $this->setValuesByArray($data['CarOwner']['Phisical'], [
            "Patronymic" => 'middleName',
        ], $owner);
        if (count($owner['documents']) > 1) {
            $data['CarOwner']['Phisical']['Documents'] = [];
            foreach ($owner['documents'] as $document) {
                $pDocument = [
                    'TypeRSA' => $document['documentType'],
                    'Number' => $document['number'],
                    'Date' => $document['dateIssue'],
                    'Exit' => $document['issuedBy'],
                    'IsPrimary' => $document['documentType'] == 'passport' ? true : false, // todo из справочника
                ];
                $this->setValuesByArray($pDocument, [
                    "Serial" => 'series',
                ], $document);
                $data['CarOwner']['Phisical']['Documents'][] = $pDocument;
            }
        } else {
            $document = array_shift($owner['documents']);
            $data['CarOwner']['Phisical']['Document'] = [
                'TypeRSA' => $document['documentType'],
                'Number' => $document['number'],
                'Date' => $document['dateIssue'],
                'Exit' => $document['issuedBy'],
                'IsPrimary' => $document['documentType'] == 'passport' ? true : false, // todo из справочника
            ];
            $this->setValuesByArray($data['CarOwner']['Phisical']['Document'], [
                "Serial" => 'series',
            ], $document);
        }
        if (count($owner['addresses']) > 1) {
            $data['CarOwner']['Phisical']['Addresses'] = [];
            foreach ($owner['addresses'] as $address) {
                $pAddress = [
                    'Type' => $address['addressType'],
                    'Country' => $address['country'], // todo из справочника
                    'AddressCode' => $address['streetKladr'],
                ];
                $this->setValuesByArray($pAddress, [
                    'Street' => 'street',
                    'Hous' => 'building',
                    'Flat' => 'flat',
                    'Index' => 'postCode',
                ], $address);
                if (!$owner['isResident']) {
                    $pAddress['AddressString'] = isset($address['region']) ? $address['region'] . ', ' : '' .
                                                isset($address['district']) ? $address['district'] . ', ' : '' .
                                                isset($address['city']) ? $address['city'] . ', ' : '' .
                                                isset($address['populatedCenter']) ? $address['populatedCenter'] . ', ' : '' .
                                                isset($address['street']) ? $address['street'] . ', ' : '' .
                                                isset($address['building']) ? $address['building'] . ', ' : '' .
                                                isset($address['flat']) ? $address['flat'] . ', ' : '';
                }
                $data['CarOwner']['Phisical']['Addresses'][] = $pAddress;
            }
        } else {
            $address = array_shift($owner['addresses']);
            $data['CarOwner']['Phisical']['Address'] = [
                'Type' => $address['addressType'],
                'Country' => $address['country'], // todo из справочника
                'AddressCode' => $address['streetKladr'],
            ];
            $this->setValuesByArray($data['CarOwner']['Phisical']['Address'], [
                'Street' => 'street',
                'Hous' => 'building',
                'Flat' => 'flat',
                'Index' => 'postCode',
            ], $address);
            if (!$owner['isResident']) {
                $data['CarOwner']['Phisical']['Address']['AddressString'] = isset($address['region']) ? $address['region'] . ', ' : '' .
                isset($address['district']) ? $address['district'] . ', ' : '' .
                isset($address['city']) ? $address['city'] . ', ' : '' .
                isset($address['populatedCenter']) ? $address['populatedCenter'] . ', ' : '' .
                isset($address['street']) ? $address['street'] . ', ' : '' .
                isset($address['building']) ? $address['building'] . ', ' : '' .
                isset($address['flat']) ? $address['flat'] . ', ' : '';
            }
        }
        if ($attributes['policy']['ownerId'] == $attributes['policy']['insurantId']) {
            $data['Insurer']['Phisical'] = $data['CarOwner']['Phisical'];
        } else {
            $insurer = $this->searchSubjectById($attributes, $attributes['policy']['insurantId']);
            if (!$insurer) {
                throw new \Exception('no insurer');
            }
            if (count($insurer['documents']) > 1) {
                $data['Insurer']['Phisical']['Documents'] = [];
                foreach ($insurer['documents'] as $document) {
                    $pDocument = [
                        'TypeRSA' => $document['documentType'],
                        'Number' => $document['number'],
                        'Date' => $document['dateIssue'],
                        'Exit' => $document['issuedBy'],
                        'IsPrimary' => $document['documentType'] == 'passport' ? true : false, // todo из справочника
                    ];
                    $this->setValuesByArray($pDocument, [
                        "Serial" => 'series',
                    ], $document);
                    $data['Insurer']['Phisical']['Documents'][] = $pDocument;
                }
            } else {
                $document = array_shift($insurer['documents']);
                $data['Insurer']['Phisical']['Document'] = [
                    'TypeRSA' => $document['documentType'],
                    'Number' => $document['number'],
                    'Date' => $document['dateIssue'],
                    'Exit' => $document['issuedBy'],
                    'IsPrimary' => $document['documentType'] == 'passport' ? true : false, // todo из справочника
                ];
                $this->setValuesByArray($data['Insurer']['Phisical']['Document'], [
                    "Serial" => 'series',
                ], $document);
            }
            if (count($insurer['addresses']) > 1) {
                $data['Insurer']['Phisical']['Addresses'] = [];
                foreach ($insurer['addresses'] as $address) {
                    $pAddress = [
                        'Type' => $address['addressType'],
                        'Country' => $address['country'], // todo из справочника
                        'AddressCode' => $address['streetKladr'],
                    ];
                    $this->setValuesByArray($pAddress, [
                        'Street' => 'street',
                        'Hous' => 'building',
                        'Flat' => 'flat',
                        'Index' => 'postCode',
                    ], $address);
                    if (!$insurer['isResident']) {
                        $pAddress['AddressString'] = isset($address['region']) ? $address['region'] . ', ' : '' .
                        isset($address['district']) ? $address['district'] . ', ' : '' .
                        isset($address['city']) ? $address['city'] . ', ' : '' .
                        isset($address['populatedCenter']) ? $address['populatedCenter'] . ', ' : '' .
                        isset($address['street']) ? $address['street'] . ', ' : '' .
                        isset($address['building']) ? $address['building'] . ', ' : '' .
                        isset($address['flat']) ? $address['flat'] . ', ' : '';
                    }
                    $data['Insurer']['Phisical']['Addresses'][] = $pAddress;
                }
            } else {
                $address = array_shift($insurer['addresses']);
                $data['Insurer']['Phisical']['Address'] = [
                    'Type' => $address['addressType'],
                    'Country' => $address['country'], // todo из справочника
                    'AddressCode' => $address['streetKladr'],
                ];
                $this->setValuesByArray($data['Insurer']['Phisical']['Address'], [
                    'Street' => 'street',
                    'Hous' => 'building',
                    'Flat' => 'flat',
                    'Index' => 'postCode',
                ], $address);
                if (!$insurer['isResident']) {
                    $data['Insurer']['Phisical']['Address']['AddressString'] = isset($address['region']) ? $address['region'] . ', ' : '' .
                    isset($address['district']) ? $address['district'] . ', ' : '' .
                    isset($address['city']) ? $address['city'] . ', ' : '' .
                    isset($address['populatedCenter']) ? $address['populatedCenter'] . ', ' : '' .
                    isset($address['street']) ? $address['street'] . ', ' : '' .
                    isset($address['building']) ? $address['building'] . ', ' : '' .
                    isset($address['flat']) ? $address['flat'] . ', ' : '';
                }
            }
        }
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

    protected function prepareDriver($driver, $driverRef)
    {
        $driverDocument = $this->searchDocumentByType($driver, 'driverLicense'); // todo справочник
        $pDriver = [
            'Face' => [
                'Resident' => $driver['isResident'],
                'Surname' => $driver['lastName'],
                'Name' => $driver['firstName'],
                'BirthDate' => $driver['birthdate'],
                'Sex' => $driver['gender'],
                'Document' => [
                    'TypeRSA' => $driverDocument['documentType'],
                    'Number' => $driverDocument['number'],
                    'Date' => $driverDocument['dateIssue'],
                ],
            ],
            'DrivingExpDate' => $driverRef['driver']['drivingLicenseIssueDateOriginal'],
        ];
        $this->setValuesByArray($pDriver['Face'], [
            'Patronymic' => 'middleName',
        ], $driver);
        $this->setValuesByArray($pDriver['Face']['Document'], [
            "Serial" => 'series',
        ], $driverDocument);
        if (count($driver['addresses']) > 1) {
            $pDriver['Face']['Addresses'] = [];
            foreach ($driver['addresses'] as $address) {
                $pAddress = [
                    'Type' => $address['addressType'],
                    'Country' => $address['country'], // todo из справочника
                    'AddressCode' => $address['streetKladr'],
                ];
                $this->setValuesByArray($pAddress, [
                    'Street' => 'street',
                    'Hous' => 'building',
                    'Flat' => 'flat',
                    'Index' => 'postCode',
                ], $address);
                if (!$driver['isResident']) {
                    $pAddress['AddressString'] = isset($address['region']) ? $address['region'] . ', ' : '' .
                    isset($address['district']) ? $address['district'] . ', ' : '' .
                    isset($address['city']) ? $address['city'] . ', ' : '' .
                    isset($address['populatedCenter']) ? $address['populatedCenter'] . ', ' : '' .
                    isset($address['street']) ? $address['street'] . ', ' : '' .
                    isset($address['building']) ? $address['building'] . ', ' : '' .
                    isset($address['flat']) ? $address['flat'] . ', ' : '';
                }
                $pDriver['Face']['Addresses'][] = $pAddress;
            }
        } else {
            $address = array_shift($driver['addresses']);
            $pDriver['Face']['Address'] = [
                'Type' => $address['addressType'],
                'Country' => $address['country'], // todo из справочника
                'AddressCode' => $address['streetKladr'],
            ];
            $this->setValuesByArray($pDriver['Face']['Address'], [
                'Street' => 'street',
                'Hous' => 'building',
                'Flat' => 'flat',
                'Index' => 'postCode',
            ], $address);
            if (!$driver['isResident']) {
                $data['Insurer']['Phisical']['Address']['AddressString'] = isset($address['region']) ? $address['region'] . ', ' : '' .
                isset($address['district']) ? $address['district'] . ', ' : '' .
                isset($address['city']) ? $address['city'] . ', ' : '' .
                isset($address['populatedCenter']) ? $address['populatedCenter'] . ', ' : '' .
                isset($address['street']) ? $address['street'] . ', ' : '' .
                isset($address['building']) ? $address['building'] . ', ' : '' .
                isset($address['flat']) ? $address['flat'] . ', ' : '';
            }
        }
    }

}
