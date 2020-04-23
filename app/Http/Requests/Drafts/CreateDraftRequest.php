<?php


namespace App\Http\Requests;


use Benfin\Requests\AbstractRequest;

class CreateDraftRequest extends AbstractRequest
{
    public function rules(): array
    {
        return [
            'subjects' => "array",
            "subjects.*.id" => "integer",
            "subjects.*.fields.lastName" => "string",
            "subjects.*.fields.firstName" => "string",
            "subjects.*.fields.middleName" => "string",
            "subjects.*.fields.birthdate" => "date|date_format:Y-m-d",
            "subjects.*.fields.birthPlace" => "string",
            "subjects.*.fields.email" => "email",
            "subjects.*.fields.gender" => "regex:/^\d$/", // TODO: in справочник
            "subjects.*.fields.citizenship" => "regex:/^\d+$/", // TODO: in справочник
            "subjects.*.fields.isResident" => "boolean", // TODO: in справочник
            "subjects.*.fields.addresses" => "array",
            "subjects.*.fields.addresses.*.address" => "array",
            "subjects.*.fields.passport.series" => "string",
            "subjects.*.fields.passport.number" => "string",
            "subjects.*.fields.passport.issuedBy" => "string",
            "subjects.*.fields.passport.dateIssue" => "date|date_format:Y-m-d",
            "subjects.*.fields.passport.subdivisionCode" => "string",
            "subjects.*.fields.phone.numberPhone" => "string",
            "car.model" => "string",
            "car.maker" => "regex:/^\d+$/",
            "car.category" => "regex:/^\d+$/",
            "car.enginePower" => "regex:/^\d+$/",
            "car.countryOfRegistration" => "regex:/^\d+$/", // TODO: in справочник
            "car.isUsedWithTrailer" => "boolean",
            "car.minWeight" => "regex:/^\d+$/",
            "car.maxWeight" => "regex:/^\d+$/",
            "car.seats" => "regex:/^\d+$/",
            "car.mileage" => "regex:/^\d+$/",
            "car.sourceAcquisition" => "regex:/^\d+$/", // TODO: in справочник
            "car.vehicleCost" => "regex:/^\d+$/",
            "car.vehicleUsage" => "regex:/^\d+$/", // TODO: in справочник
            "car.vin" => "string",
            "car.regNumber" => "string",
            "car.year" => "string|min:4|max:4",
            "car.document.documentType" => "string", // TODO: in справочник
            "car.document.series" => "string",
            "car.document.number" => "string",
            "car.document.dateIssue" => "date|date_format:Y-m-d",
            "car.inspection.series" => "string",
            "car.inspection.number" => "string",
            "car.inspection.dateIssue" => "date|date_format:Y-m-d",
            "car.inspection.dateEnd" => "date|date_format:Y-m-d",
            'policy.beginDate' => "date|date_format:Y-m-d",
            'policy.endDate' => "date|date_format:Y-m-d",
            'policy.insurantId' => "integer",
            'policy.ownerId' => "integer",
            'policy.isMultidrive' => "boolean",
            'policy.policyProcessingRegion' => "string",
            'drivers' => "array",
            'drivers.*.driver.drivingLicenseIssueDateOriginal' => "date|date_format:Y-m-d",
        ];
    }

}
