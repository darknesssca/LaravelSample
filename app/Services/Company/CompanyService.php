<?php


namespace App\Services\Company;

use App\Models\InsuranceCompany;
use GuzzleHttp\Client;
use Illuminate\Support\Carbon;

class CompanyService implements CompanyServiceInterface
{
    public $companyCode;
    public $companyId;

    public function run(InsuranceCompany $company, $attributes, $additionalData): array
    {
        //
    }

    public function validationRulesForm(): array
    {
        return [
            'auth_token' => "required",
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
            "subjects.*.fields.phone" => "required",
            "subjects.*.fields.phone.numberPhone" => "required|string",
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
            "car.year" => "required|string|min:4|max:4",
            "car.documents" => "required|array",
            "car.documents.*.document" => "required",
            "car.documents.*.document.documentType" => "required|string", // TODO: in справочник
            "car.documents.*.document.documentSeries" => "required|string", // TODO: in справочник
            "car.documents.*.document.documentNumber" => "required|string", // TODO: in справочник
            "car.documents.*.document.documentIssued" => "required|string", // TODO: in справочник
            "car.inspection.documentType" => "required|integer", // TODO: in справочник
            "car.inspection.documentSeries" => "required|string",
            "car.inspection.documentNumber" => "required|string",
            "car.inspection.documentIssuedDate" => "required|date|date_format:Y-m-d",
            "car.inspection.documentDateEmd" => "required|date|date_format:Y-m-d",
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

    public function validationMessagesForm(): array
    {
        return [];
    }

    public function validationRulesProcess(): array
    {
        return [
            'token' => "required|string|min:32|max:32",
        ];
    }

    public function validationMessagesProcess(): array
    {
        return [];
    }

    public function setValue(&$target, $targetName, $sourceName, $source)
    {
        if (isset($source[$sourceName]) && $source[$sourceName]) {
            $target[$targetName] = $source[$sourceName];
        }
    }

    public function setValuesByArray(&$target, $dependencies, $source)
    {
        foreach ($dependencies as $targetName => $sourceName) {
            if (isset($source[$sourceName]) && $source[$sourceName]) {
                if (gettype($source[$sourceName]) == 'array') {
                    continue;
                }
                $target[$targetName] = $source[$sourceName];
            }
        }
    }

    protected function formatDateTimeZone($date)
    {
        $date = Carbon::createFromFormat('Y-m-d', $date);
        return $date->format('Y-m-d\TH:i:sP');
    }

    protected function formatDateToRuFormat($date)
    {
        $date = Carbon::createFromFormat('Y-m-d', $date);
        return $date->format('d-m-Y');
    }

    protected function formatDateTime($date)
    {
        $date = Carbon::createFromFormat('Y-m-d', $date);
        return $date->format('Y-m-d\TH:i:s');
    }

    protected function searchDocumentByTypeAndId($attributes, $subjectId, $type)
    {
        foreach ($attributes['subjects'] as $iSubject => $subject) {
            if ($subject['id'] != $subjectId) {
                continue;
            }
            foreach ($subject['fields']['documents'] as $iDocument => $document) {
                if ($document['document']['documentType'] == $type) { // TODO значение из справочника
                    return $document['document'];
                }
            }
        }
        return false;
    }

    protected function searchDocumentByType($subject, $type)
    {
        foreach ($subject['documents'] as $iDocument => $document) {
            if ($document['document']['documentType'] == $type) { // TODO значение из справочника
                return $document['document'];
            }
        }
        return false;
    }

    protected function searchAddressByType($subject, $type)
    {
        foreach ($subject['addresses'] as $iAddress => $address) {
            if ($address['address']['addressType'] == $type) { // TODO значение из справочника
                return $address['address'];
            }
        }
        return false;
    }

    protected function searchSubjectById($attributes, $subjectId)
    {
        foreach ($attributes['subjects'] as $iSubject => $subject) {
            if ($subject['id'] == $subjectId) {
                return $subject['fields'];
            }
        }
        return false;
    }

    protected function searchDrivers($attributes)
    {
        $driversList = [];
        foreach ($attributes['drivers'] as $driver) {
            foreach ($attributes['subjects'] as $subject) {
                if ($subject['id'] == $driver['driver']['driverId']) {
                    $driversList[$subject['id']] = $subject['fields'];
                }
            }
        }
        return $driversList;
    }

}
