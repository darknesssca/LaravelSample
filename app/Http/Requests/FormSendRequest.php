<?php


namespace App\Http\Requests;


use Benfin\Requests\AbstractRequest;
use Carbon\Carbon;
use Illuminate\Validation\Rule;

class FormSendRequest extends AbstractRequest
{
    public function rules(): array
    {
        //Проверка года выпуска авто
        $expired_car_year = function () {
            $year = (int)$this->get('car')['year'];
            $current_year = Carbon::now()->year;

            if ($current_year - $year >= 4) {
                return true;
            }

            return false;
        };

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
            "car.mileage" => "integer|nullable",
            "car.sourceAcquisition" => "nullable|integer|exists:source_acquisitions,id",
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
            "car.inspection.number" => [
                Rule::requiredIf($expired_car_year),
                'numeric',
            ],
            "car.inspection.dateIssue" => [
                'date',
                'date_format:Y-m-d',
                Rule::requiredIf($expired_car_year),
            ],
            "car.inspection.dateEnd" => [
                'date',
                'date_format:Y-m-d',
                Rule::requiredIf($expired_car_year),
            ],
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
            'prevToken' => 'string|size:32'
        ];
    }
}
