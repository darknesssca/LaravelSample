<?php


namespace App\Http\Requests;


use Benfin\Requests\AbstractRequest;

class FormSendRequest extends AbstractRequest
{
    public function rules():array
    {
        return [
            'draftId' => "integer",
            'number' => "string",
            'subjects' => "required|array",
            "subjects.*.id" => "required|integer",
            "subjects.*.fields.lastName" => "required|string",
            "subjects.*.fields.firstName" => "required|string",
            "subjects.*.fields.middleName" => "string",
            "subjects.*.fields.birthdate" => "required|date|date_format:Y-m-d",
            "subjects.*.fields.birthPlace" => "string",
            "subjects.*.fields.email" => "email",
            "subjects.*.fields.phone" => "string",
            "subjects.*.fields.gender" => "required|integer",
            "subjects.*.fields.citizenship" => "required|integer|exists:countries,id",
            "subjects.*.fields.addresses" => "sometimes|array",
            "subjects.*.fields.documents" => "required|array",
            "subjects.*.fields.documents.*.document" => "required",
            "subjects.*.fields.documents.*.document.documentType" => "required|string|in:passport,license",
            "subjects.*.fields.documents.*.document.isRussian" => "required|boolean",
            "subjects.*.fields.documents.*.document.series" => "string",
            "subjects.*.fields.documents.*.document.number" => "required|string",
            "subjects.*.fields.documents.*.document.issuedBy" => "string",
            "subjects.*.fields.documents.*.document.dateIssue" => "required|date|date_format:Y-m-d",
            "subjects.*.fields.documents.*.document.validTo" => "date|date_format:Y-m-d",
            "subjects.*.fields.documents.*.document.subdivisionCode" => "string",
            'car' => "required",
            "car.model" => "required|string",
            "car.maker" => "required|integer|exists:car_marks,id",
            "car.category" => "required|integer|exists:car_categories,id",
            "car.enginePower" => "required|integer",
            "car.countryOfRegistration" => "required|integer|exists:countries,id",
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
            "car.year" => "required|string|min:4|max:4",
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
            'policy.insurantId' => "required|integer|address_required|birthplace_required|email_required",
            'policy.ownerId' => "required|integer|address_required|birthplace_required|email_required",
            'policy.isMultidrive' => "required|boolean",
            'drivers' => "array|required_if:policy.isMultidrive,false",
            'drivers.*.driver' => "required",
            'drivers.*.driver.driverId' => "required|integer|driver_foreign_license",
            'drivers.*.driver.drivingLicenseIssueDateOriginal' => "required|date|date_format:Y-m-d",
        ];
    }
}
