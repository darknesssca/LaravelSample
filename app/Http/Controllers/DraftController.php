<?php

namespace App\Http\Controllers;

use App\Models\DraftClient;
use App\Models\Policy;
use App\Models\PolicyStatus;
use App\Models\PolicyType;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Nowakowskir\JWT\TokenEncoded;

class DraftController extends Controller
{
    public function index(Request $request)
    {
        $attributes = $this->validate(
            $request,
            [
                'auth_token' => 'required|string',
            ],
            []
        );
        RestController::checkToken($attributes);
        $tokenEncoded = new TokenEncoded($attributes['auth_token']);
        $payload = $tokenEncoded->decode()->getPayload();
        $userId = $payload['user_id'];
        if (!$userId) {
            return $this->error('user not parsed', 400);
        }
        return Policy::getPolicies($userId);
    }

    public function show($id, Request $request)
    {
        $attributes = $this->validate(
            $request,
            [
                'auth_token' => 'required|string'
            ],
            []
        );
        RestController::checkToken($attributes);
        $tokenEncoded = new TokenEncoded($attributes['auth_token']);
        $payload = $tokenEncoded->decode()->getPayload();
        $userId = $payload['user_id'];
        $id = (int)$id;
        if (!$userId) {
            return $this->error('user not parsed', 400);
        }
        if (!$id) {
            return $this->error('id not correct', 400);
        }
        return Policy::getPolicyById($id);
    }

    public function store(Request $request)
    {
        $attributes = $this->validate(
            $request,
            $this->validationRulesForm(),
            []
        );
        RestController::checkToken($attributes);
        $tokenEncoded = new TokenEncoded($attributes['auth_token']);
        $payload = $tokenEncoded->decode()->getPayload();
        $userId = $payload['user_id'];
        if (!$userId) {
            return $this->error('user not parsed', 400);
        }
        //policy
        $policyData = [
            'agent_id' => $userId,
            'status_id' => PolicyStatus::where('code', 'draft')->get()->first()->id,
            'type_id' => PolicyType::where('code', 'osago')->get()->first()->id,
        ];
        $this->pushData($policyData, $attributes['policy'], [
            'region_id' => 'policyProcessingRegion',
            'is_multi_drive' => 'isMultidrive',
        ]);
        if (isset($attributes['policy']['beginDate']) && $attributes['policy']['beginDate']) {
            $policyData['start_date'] = Carbon::createFromFormat('Y-m-d', $attributes['policy']['beginDate']);
        }
        if (isset($attributes['policy']['endDate']) && $attributes['policy']['endDate']) {
            $policyData['end_date'] = Carbon::createFromFormat('Y-m-d', $attributes['policy']['endDate']);
        }
        //subjects
        $subjects = [];
        if (
            isset($attributes['subjects']) && $attributes['subjects'] &&
            (
                (isset($attributes['policy']['ownerId']) && $attributes['policy']['ownerId']) ||
                (isset($attributes['policy']['insurantId']) && $attributes['policy']['insurantId'])
            )
        ) {
            foreach ($attributes['subjects'] as $subject) {
                $subjectData = [];
                $this->pushData($subjectData, $subject['fields'], [
                    'last_name' => 'lastName',
                    'first_name' => 'firstName',
                    'patronymic' => 'middleName',
                    'birth_place' => 'birthPlace',
                    'gender_id' => 'gender',
                    'address' => 'address',
                    'phone' => 'phone',
                    'email' => 'email',
                    'citizenship_id' => 'citizenship',
                    'is_russian' => 'isResident',
                ]);
                if (isset($subject['fields']['passport']) && $subject['fields']['passport']) {
                    $this->pushData($subjectData, $subject['fields']['passport'], [
                        'passport_series' => 'series',
                        'passport_number' => 'number',
                        'passport_date' => 'dateIssue',
                        'passport_issuer' => 'issuedBy',
                        'passport_unit_code' => 'subdivisionCode',
                    ]);
                }
                if (isset($subject['fields']['birthdate']) && $subject['fields']['birthdate']) {
                    $subjectData['birth_date'] = Carbon::createFromFormat('Y-m-d', $subject['fields']['birthdate']);
                }
                if (
                    isset($attributes['policy']['ownerId']) && $attributes['policy']['ownerId'] &&
                    isset($attributes['policy']['insurantId']) && $attributes['policy']['insurantId'] &&
                    ($attributes['policy']['insurantId'] == $attributes['policy']['ownerId']) &&
                    ($subject['id'] == $attributes['policy']['ownerId'])
                ) {
                    $result = DraftClient::create($subjectData);
                    $policyData['client_id'] = $result->id;
                    $policyData['insurant_id'] = $result->id;
                } elseif (isset($attributes['policy']['ownerId']) && $attributes['policy']['ownerId'] && ($subject['id'] == $attributes['policy']['ownerId'])) { // если это овнер
                    $result = DraftClient::create($subjectData);
                    $policyData['client_id'] = $result->id;
                } elseif (isset($attributes['policy']['insurantId']) && $attributes['policy']['insurantId'] && ($subject['id'] == $attributes['policy']['insurantId'])) {
                    $result = DraftClient::create($subjectData);
                    $policyData['insurant_id'] = $result->id;
                } else {
                    continue;
                }
            }
        }
        //car
        if (isset($attributes['car']) && $attributes['car']) {
            $this->pushData($subjectData, $attributes['car'], [
                'vehicle_model_id' => 'model',
                'vehicle_engine_power' => 'enginePower',
                'vehicle_vin' => 'vin',
                'vehicle_reg_country' => 'countryOfRegistration',
                'vehicle_made_year' => 'year',
                'vehicle_unladen_mass' => 'minWeight',
                'vehicle_loaded_mass' => 'maxWeight',
                'vehicle_count_seats' => 'seats',
                'vehicle_mileage' => 'mileage',
                'vehicle_cost' => 'vehicleCost',
                'vehicle_acquisition' => 'sourceAcquisition',
                'vehicle_usage_target' => 'vehicleUsage',
                'vehicle_usage_type' => 'usageType',
                'vehicle_with_trailer' => 'isUsedWithTrailer',
            ]);
            if (isset($attributes['car']['document']) && $attributes['car']['document']) {
                $this->pushData($policyData, $attributes['car']['document'], [
                    'vehicle_reg_doc_type_id' => 'documentType',
                    'vehicle_doc_series' => 'documentSeries',
                    'vehicle_doc_number' => 'documentNumber',
                    'vehicle_doc_issued' => 'documentIssued',
                ]);
            }
            if (isset($attributes['car']['docInspection']) && $attributes['car']['docInspection']) {
                $this->pushData($policyData, $attributes['car']['docInspection'], [
                    'vehicle_inspection_doc_series' => 'documentSeries',
                    'vehicle_inspection_doc_number' => 'documentNumber',
                ]);
            }
        }
        $policy = Policy::create($policyData);
        return $policy;
        if (isset($attributes['drivers']) && $attributes['drivers']) {
            foreach ($attributes['drivers'] as $driver) {
                $driverData = [];
                $this->pushData($driverData, $driver, [
                    'first_name' => 'firstName',
                    'last_name' => 'lastName',
                    'patronymic' => 'middleName',
                    'license_series' => 'license_series',
                    'license_number' => 'license_number',
                ]);
                if (isset($driver['birthdate']) && $driver['birthdate']) {
                    $driverData['birth_date'] = Carbon::createFromFormat('Y-m-d', $driver['birthdate']);
                }
                if (isset($driver['license_date']) && $driver['license_date']) {
                    $driverData['license_date'] = Carbon::createFromFormat('Y-m-d', $driver['license_date']);
                }
                if (isset($driver['drivingLicenseIssueDateOriginal']) && $driver['drivingLicenseIssueDateOriginal']) {
                    $driverData['exp_start_date'] = Carbon::createFromFormat('Y-m-d', $driver['drivingLicenseIssueDateOriginal']);
                }
                $policy->drivers()->create($driverData);
            }
        }
        return [true];
    }

    public function update($id, Request $request)
    {
        $attributes = $this->validate(
            $request,
            $this->validationRulesForm(),
            []
        );
        RestController::checkToken($attributes);
    }

    public function delete($id, Request $request)
    {
        $attributes = $this->validate(
            $request,
            [
                'auth_token' => 'required|string'
            ],
            []
        );
        RestController::checkToken($attributes);
        $tokenEncoded = new TokenEncoded($attributes['auth_token']);
        $payload = $tokenEncoded->decode()->getPayload();
        $userId = $payload['user_id'];
        if (!$userId) {
            return $this->error('user not parsed', 400);
        }
        $policy = Policy::where('id', $id)->where('agent_id', $userId)->first()->get();
        return $policy;
    }

    protected function pushData(&$target, $source, $relations)
    {
        foreach ($relations as $targetName => $sourceName) {
            if (isset($source[$sourceName]) && $source[$sourceName]) {
                $target[$targetName] = $source[$sourceName];
            }
        }
    }

    public function validationRulesForm(): array
    {
        return [
            'auth_token' => "required",
            'subjects' => "array",
            "subjects.*.id" => "integer",
            "subjects.*.fields.lastName" => "string",
            "subjects.*.fields.firstName" => "string",
            "subjects.*.fields.middleName" => "string",
            "subjects.*.fields.birthdate" => "date|date_format:Y-m-d",
            "subjects.*.fields.birthPlace" => "string",
            "subjects.*.fields.email" => "email",
            "subjects.*.fields.gender" => "integer", // TODO: in справочник
            "subjects.*.fields.citizenship_id" => "string", // TODO: in справочник
            "subjects.*.fields.isResident" => "boolean", // TODO: in справочник
            "subjects.*.fields.address" => "string",
            "subjects.*.fields.passport.series" => "string",
            "subjects.*.fields.passport.number" => "string",
            "subjects.*.fields.passport.issuedBy" => "string",
            "subjects.*.fields.passport.dateIssue" => "date|date_format:Y-m-d",
            "subjects.*.fields.passport.subdivisionCode" => "string",
            "subjects.*.fields.phone.numberPhone" => "string",
            "car.model" => "string", // TODO: in справочник
            "car.enginePower" => "integer",
            "car.countryOfRegistration" => "string", // TODO: in справочник
            "car.isUsedWithTrailer" => "boolean",
            "car.minWeight" => "integer",
            "car.maxWeight" => "integer",
            "car.seats" => "integer",
            "car.mileage" => "integer",
            "car.sourceAcquisition" => "integer", // TODO: in справочник
            "car.vehicleCost" => "integer",
            "car.vehicleUsage" => "integer", // TODO: in справочник
            "car.vin" => "string",
            "car.year" => "string|min:4|max:4",
            "car.document.documentType" => "integer", // TODO: in справочник
            "car.document.documentSeries" => "string", // TODO: in справочник
            "car.document.documentNumber" => "string", // TODO: in справочник
            "car.document.documentIssued" => "string", // TODO: in справочник
            "car.docInspection.vehicle_inspection_doc_series" => "string", // TODO: in справочник
            "car.docInspection.vehicle_inspection_doc_number" => "string", // TODO: in справочник
            'policy.beginDate' => "date|date_format:Y-m-d",
            'policy.endDate' => "date|date_format:Y-m-d",
            'policy.insurantId' => "integer",
            'policy.ownerId' => "integer",
            'policy.isMultidrive' => "boolean",
            'policy.policyProcessingRegion' => "string",
            'drivers' => "array",
            'drivers.*.lastName' => "string",
            'drivers.*.firstName' => "string",
            'drivers.*.middleName' => "string",
            'drivers.*.birthdate' => "date|date_format:Y-m-d",
            'drivers.*.license_series' => "string",
            'drivers.*.license_number' => "string",
            'drivers.*.license_date' => "date|date_format:Y-m-d",
            'drivers.*.driver.drivingLicenseIssueDateOriginal' => "date|date_format:Y-m-d",
        ];
    }

    protected function error($messages, $httpCode = 500)
    {
        $errors = [];
        if (gettype($messages) == 'array') {
            foreach ($messages as $message) {
                $errors[] = [
                    'message' => $message,
                ];
            }
        } else {
            $errors[] = [
                'message' => (string)$messages,
            ];
        }
        $message = [
            'error' => true,
            'errors' => $errors,
        ];
        return response()->json($message, $httpCode);
    }
}
