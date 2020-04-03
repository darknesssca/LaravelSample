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
            "subjects.*.fields.phone" => "string",
            "subjects.*.fields.gender" => "required|integer",
            "subjects.*.fields.citizenship" => "required|integer|exists:countries,id",
            "subjects.*.fields.addresses" => "array",
            "subjects.*.fields.addresses.*.address.addressType" => "required|string|in:registration,home",
            "subjects.*.fields.addresses.*.address.country" => "required|integer|exists:countries,id",
            "subjects.*.fields.addresses.*.address.postCode" => "string",
            "subjects.*.fields.addresses.*.address.region" => "required|string",
            "subjects.*.fields.addresses.*.address.regionKladr" => "string",
            "subjects.*.fields.addresses.*.address.district" => "required|string",
            "subjects.*.fields.addresses.*.address.districtKladr" => "string",
            "subjects.*.fields.addresses.*.address.city" => "string",
            "subjects.*.fields.addresses.*.address.cityKladr" => "string",
            "subjects.*.fields.addresses.*.address.populatedCenter" => "string",
            "subjects.*.fields.addresses.*.address.populatedCenterKladr" => "string",
            "subjects.*.fields.addresses.*.address.street" => "required|string",
            "subjects.*.fields.addresses.*.address.streetKladr" => "string",
            "subjects.*.fields.addresses.*.address.building" => "string",
            "subjects.*.fields.addresses.*.address.buildingKladr" => "string",
            "subjects.*.fields.addresses.*.address.flat" => "string",
            "subjects.*.fields.document" => "required|array",
            "subjects.*.fields.document.*.documentType" => "required|string|in:passport,license",
            "subjects.*.fields.document.*.isRussian" => "required|boolean",
            "subjects.*.fields.document.*.series" => "string",
            "subjects.*.fields.document.*.number" => "required|string",
            "subjects.*.fields.document.*.issuedBy" => "string",
            "subjects.*.fields.document.*.dateIssue" => "required|date|date_format:Y-m-d",
            "subjects.*.fields.document.*.validTo" => "date|date_format:Y-m-d",
            "subjects.*.fields.document.*.subdivisionCode" => "string",
            'car' => "required",
            "car.model" => "required|string",
            "car.maker" => "required|integer|exists:car_marks,id",
            "car.category" => "required|integer|exists:car_categories,id",
            "car.enginePower" => "required|integer",
            "car.countryOfRegistration" => "required|integer",
            "car.isUsedWithTrailer" => "required|boolean",
            "car.minWeight" => "integer",
            "car.maxWeight" => "integer",
            "car.seats" => "integer",
            "car.mileage" => "required|integer",
            "car.sourceAcquisition" => "required|integer|exists:source_acquisitions,id",
            "car.vehicleCost" => "required|integer",
            "car.vehicleUsage" => "required|integer",
            "car.isIrregularVIN" => "required|boolean",
            "car.vin" => "required|string",
            "car.regNumber" => "string|required_if:car.document.documentType,sts",
            "car.year" => "required|integer|min:4|max:4",
            "car.document" => "required",
            "car.document.documentType" => "required|string|in:sts,pts",
            "car.document.series" => "string",
            "car.document.number" => "required|string",
            "car.document.dateIssue" => "required|string",
            "car.inspection" => "required",
            "car.inspection.series" => "string",
            "car.inspection.number" => "required|string",
            "car.inspection.dateIssue" => "required|date|date_format:Y-m-d",
            "car.inspection.dateEnd" => "required|date|date_format:Y-m-d",
            'policy' => "required",
            'policy.beginDate' => "required|date|date_format:Y-m-d|before:policy.endDate",
            'policy.endDate' => "required|date|date_format:Y-m-d|after:policy.beginDate",
            'policy.insurantId' => "required|integer",
            'policy.ownerId' => "required|integer",
            'policy.isMultidrive' => "required|boolean",
            'drivers' => "required|array",
            'drivers.*.driver' => "required",
            'drivers.*.driver.driverId' => "required|integer",
            'drivers.*.driver.drivingLicenseIssueDateOriginal' => "required|date|date_format:Y-m-d",
        ];
    }
}
