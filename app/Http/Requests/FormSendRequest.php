<?php


namespace App\Http\Requests;


use Benfin\Requests\AbstractRequest;

class FormSendRequest extends AbstractRequest
{
    public function rules():array
    {
        return [
            'draftId' => "integer",
            'subjects' => "required|array",
            "subjects.*.id" => "required|integer",
            "subjects.*.fields.lastName" => "required|string",
            "subjects.*.fields.firstName" => "required|string",
            "subjects.*.fields.middleName" => "string",
            "subjects.*.fields.birthdate" => "required|date|date_format:Y-m-d",
            "subjects.*.fields.birthPlace" => "required|string",
            "subjects.*.fields.email" => "email",
            "subjects.*.fields.gender" => "required|string", // TODO: in справочник
            "subjects.*.fields.citizenship" => "required|string", // TODO: in справочник
            "subjects.*.fields.isResident" => "required|boolean", // TODO: in справочник
            "subjects.*.fields.addresses" => "required|array",
            "subjects.*.fields.addresses.*.address.addressType" => "required|string", // TODO: in справочник
            "subjects.*.fields.addresses.*.address.country" => "required|string", // TODO: in справочник
            "subjects.*.fields.addresses.*.address.postCode" => "string",
            "subjects.*.fields.addresses.*.address.region" => "required|string", // TODO: in справочник
            "subjects.*.fields.addresses.*.address.regionKladr" => "required|string",
            "subjects.*.fields.addresses.*.address.district" => "required|string",
            "subjects.*.fields.addresses.*.address.districtKladr" => "string",
            "subjects.*.fields.addresses.*.address.city" => "string",
            "subjects.*.fields.addresses.*.address.cityKladr" => "string",
            "subjects.*.fields.addresses.*.address.populatedCenter" => "string",
            "subjects.*.fields.addresses.*.address.populatedCenterKladr" => "string",
            "subjects.*.fields.addresses.*.address.street" => "required|string",
            "subjects.*.fields.addresses.*.address.streetKladr" => "required|string",
            "subjects.*.fields.addresses.*.address.building" => "required|string",
            "subjects.*.fields.addresses.*.address.buildingKladr" => "string",
            "subjects.*.fields.addresses.*.address.flat" => "required|string",
//            "subjects.*.fields.document" => "array",
            "subjects.*.fields.document.*.documentType" => "required|string", // TODO: in справочник
            "subjects.*.fields.document.*.series" => "string",
            "subjects.*.fields.document.*.number" => "required|string",
            "subjects.*.fields.document.*.issuedBy" => "required|string",
            "subjects.*.fields.document.*.dateIssue" => "required|date|date_format:Y-m-d",
            "subjects.*.fields.document.*.validTo" => "date|date_format:Y-m-d",
            "subjects.*.fields.document.*.subdivisionCode" => "string",
            "subjects.*.fields.phone" => "required|string",
            'car' => "required",
            "car.model" => "required|string", // TODO: in справочник
            "car.maker" => "required|string", // TODO: in справочник
            "car.enginePower" => "required|integer",
            "car.countryOfRegistration" => "required|string", // TODO: in справочник
            "car.isUsedWithTrailer" => "required|boolean",
            "car.minWeight" => "integer",
            "car.maxWeight" => "integer",
            "car.seats" => "integer",
            "car.mileage" => "required|integer",
            "car.sourceAcquisition" => "required|string", // TODO: in справочник
            "car.vehicleCost" => "required|integer",
            "car.vehicleUsage" => "required|string", // TODO: in справочник
            "car.vehicleUseRegion" => "required|string", // TODO: in справочник
            "car.isIrregularVIN" => "required|boolean",
            "car.vin" => "required|string",
            "car.regNumber" => "string", // todo required_if если тип дока машины СТС
            "car.year" => "required|string|min:4|max:4",
            "car.document" => "required",
            "car.document.documentType" => "required|string", // TODO: in справочник
            "car.document.series" => "required|string", // TODO: in справочник
            "car.document.number" => "required|string", // TODO: in справочник
            "car.document.dateIssue" => "required|string", // TODO: in справочник
            "car.inspection" => "required",
            "car.inspection.series" => "required|string",
            "car.inspection.number" => "required|string",
            "car.inspection.dateIssue" => "required|date|date_format:Y-m-d",
            "car.inspection.dateEnd" => "required|date|date_format:Y-m-d",
            'policy' => "required",
            'policy.beginDate' => "required|date|date_format:Y-m-d",
            'policy.endDate' => "required|date|date_format:Y-m-d",
            'policy.insurantId' => "required|integer",
            'policy.ownerId' => "required|integer",
            'policy.isMultidrive' => "required|boolean",
            'drivers' => "required|array",
            'drivers.*.driver' => "required",
            'drivers.*.driver.driverId' => "integer",
            'drivers.*.driver.drivingLicenseIssueDateOriginal' => "date|date_format:Y-m-d",
        ];
    }
}
