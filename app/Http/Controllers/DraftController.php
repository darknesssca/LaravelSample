<?php

namespace App\Http\Controllers;

use App\Models\Policy;
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
        if ($attributes['subject']) {
            foreach ($attributes['subject'] as $subject) {
                $data = [];
                $this->prepareData($data, $subject['fields'], [
                    'last_name' => 'lastName',
                    'first_name' => 'firstName',
                    'patronymic' => 'middleName',
                    'birth_date' => 'birthdate',
//                    'passport_series' => '',
//                    'passport_number' => '',
//                    'passport_date' => '',
//                    'passport_issuer' => '',
//                    'passport_unit_code' => '',
                    'address' => '',
                    'is_russian' => '',
                ]);

            }
        }
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
    }

    protected function prepareData(&$target, $source, $relations)
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
            "subjects.*.fields.gender" => "string", // TODO: in справочник
            "subjects.*.fields.citizenship" => "string", // TODO: in справочник
            "subjects.*.fields.isResident" => "boolean", // TODO: in справочник
            "subjects.*.fields.address" => "string",
            "subjects.*.fields.document.*.documentType" => "string", // TODO: in справочник
            "subjects.*.fields.document.*.series" => "string",
            "subjects.*.fields.document.*.number" => "string",
            "subjects.*.fields.document.*.issuedBy" => "string",
            "subjects.*.fields.document.*.dateIssue" => "date|date_format:Y-m-d",
            "subjects.*.fields.document.*.validTo" => "date|date_format:Y-m-d",
            "subjects.*.fields.document.*.subdivisionCode" => "string",
            "subjects.*.fields.phone.numberPhone" => "string",
            "car.model" => "string", // TODO: in справочник
            "car.maker" => "string", // TODO: in справочник
            "car.enginePower" => "integer",
            "car.countryOfRegistration" => "string", // TODO: in справочник
            "car.isUsedWithTrailer" => "boolean",
            "car.minWeight" => "integer",
            "car.maxWeight" => "integer",
            "car.seats" => "integer",
            "car.mileage" => "integer",
            "car.sourceAcquisition" => "string", // TODO: in справочник
            "car.vehicleCost" => "integer",
            "car.vehicleUsage" => "string", // TODO: in справочник
            "car.vehicleUseRegion" => "string", // TODO: in справочник
            "car.isIrregularVIN" => "boolean",
            "car.vin" => "string",
            "car.year" => "string|min:4|max:4",
            "car.documents" => "array",
            "car.documents.*.document.documentType" => "string", // TODO: in справочник
            "car.documents.*.document.documentSeries" => "string", // TODO: in справочник
            "car.documents.*.document.documentNumber" => "string", // TODO: in справочник
            "car.documents.*.document.documentIssued" => "string", // TODO: in справочник
            'policy.beginDate' => "date|date_format:Y-m-d",
            'policy.endDate' => "date|date_format:Y-m-d",
            'policy.insurantId' => "integer",
            'policy.ownerId' => "integer",
            'policy.isMultidrive' => "boolean",
            'drivers' => "array",
            'drivers.*.driver.driverId' => "integer",
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
