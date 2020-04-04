<?php

namespace App\Services;

use App\Contracts\Repositories\DraftRepositoryContract;
use App\Contracts\Repositories\PolicyRepositoryContract;
use App\Contracts\Services\PolicyServiceContract;
use App\Traits\ValueSetterTrait;
use Benfin\Api\Contracts\CommissionCalculationMicroserviceContract;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;

class PolicyService implements PolicyServiceContract
{
    use ValueSetterTrait;

    private $policyRepository;

    public function __construct(PolicyRepositoryContract $policyRepository)
    {
        $this->policyRepository = $policyRepository;
    }

    public function getList(array $filter = [])
    {
        $policies =  $this->policyRepository->getList($filter);

        return $policies->map(function ($policy) {
            $policy['rewards'] = app(CommissionCalculationMicroserviceContract::class)->getRewards($policy->id);
        });
    }

    public function create(array $fields, int $draftId = null)
    {
        if ($draftId) {
            $draft = app(DraftRepositoryContract::class)->getById($draftId);
            $fields = array_merge(
                $draft->all(),
                $fields
            );
        }

        $policy = $this->policyRepository->create($fields);

        if ($drivers = $fields['drivers']) {
            foreach ($drivers as &$driver) {
                if (isset($driver['birthdate']) && $driver['birthdate']) {
                    $driver['birth_date'] = Carbon::createFromFormat('Y-m-d', $driver['birthdate']);
                }
                if (isset($driver['license_date']) && $driver['license_date']) {
                    $driver['license_date'] = Carbon::createFromFormat('Y-m-d', $driver['license_date']);
                }
                if (isset($driver['drivingLicenseIssueDateOriginal']) && $driver['drivingLicenseIssueDateOriginal']) {
                    $driver['exp_start_date'] = Carbon::createFromFormat('Y-m-d', $driver['drivingLicenseIssueDateOriginal']);
                }
                $policy->drivers()->create($driver);
            }
        }

        if ($draftId && $draft) {
            foreach ($draft->drivers as $driver) {
                $policy->drivers()->attach($driver->id);
            }

            $draft->drivers()->detach();
            $draft->delete();
        }

        app(CommissionCalculationMicroserviceContract::class)->createRewards($policy->id, $policy->registration_date, $policy->region_kladr, GlobalStorage::getUserId());

        return $policy->id;
    }

    public function statistic(array $filter = [])
    {
        /**
         * @var Collection $policies
         */
        $policies =  $this->policyRepository->getList($filter);

        return [
            'count' => $policies->count(),
            'sum' => $policies->sum('premium')
        ];
    }

    public function createPolicyFromCustomData($company, $attributes)
    {
        $fields = [
            'insurance_company_id' => $company->id,
            'subjects' => [],
            'car' => [],
            'drivers' => [],
        ];
        if (isset($attributes['number'])) {
            $fields['number'] = $attributes['number'];
        }
        foreach ($attributes['subjects'] as $subject) {
            $pSubject = [
                'id' => $subject['id'],
                'fields' => [],
            ];
            $this->setValuesByArray($pSubject['fields'], [
                'lastName' => 'lastName',
                'firstName' => 'firstName',
                'birthdate' => 'birthdate',
                'birthPlace' => 'birthPlace',
                'email' => 'email',
                'gender' => 'gender',
                'citizenship' => 'citizenship',
                'phone' => 'phone',
            ], $subject['fields']);
            foreach ($subject['addresses'] as $address) {
                if ($address['address']['addressType'] == 'registration') {
                    $pSubject['fields']['address'] = $address['address'];
                }
            }
            foreach ($subject['documents'] as $document) {
                if ($document['document']['documentType'] == 'passport') {
                    $pSubject['fields']['passport'] = $document['document'];
                }
            }
            $fields['subjects'] = $pSubject;
        }
        $this->setValuesByArray($fields['car'], [
            'model' => 'model',
            'maker' => 'maker',
            'countryOfRegistration' => 'countryOfRegistration',
            'isUsedWithTrailer' => 'isUsedWithTrailer',
            'mileage' => 'mileage',
            'sourceAcquisition' => 'sourceAcquisition',
            'vehicleCost' => 'vehicleCost',
            'vehicleUsage' => 'vehicleUsage',
            'vin' => 'vin',
            'regNumber' => 'regNumber',
            'year' => 'year',
            'minWeight' => 'minWeight',
            'maxWeight' => 'maxWeight',
            'seats' => 'seats',
        ], $attributes['car']);
        $fields['car']['document'] = $attributes['car']['document'];
        $fields['car']['inspection'] = $attributes['car']['inspection'];
        $fields['policy'] = $attributes['policy'];
        foreach ($attributes['drivers'] as $driver) {
            foreach ($attributes['subjects'] as $subject) {
                if ($subject['id'] == $driver['driver']['driverId']) {
                    $pDriver = [];
                    $this->setValuesByArray($pDriver, [
                        'lastName' => 'lastName',
                        'firstName' => 'firstName',
                        'birthdate' => 'birthdate',
                    ], $subject['fields']);
                    $this->setValuesByArray($pDriver, [
                        'drivingLicenseIssueDateOriginal' => 'drivingLicenseIssueDateOriginal',
                    ], $driver['driver']);
                    foreach ($subject['documents'] as $document) {
                        if ($document['document']['documentType'] == 'license') {
                            $this->setValuesByArray($pDriver, [
                                'license_series' => 'series',
                                'license_number' => 'number',
                                'license_date' => 'dateIssue',
                            ], $document['document']);
                        }
                    }
                    $fields['drivers'][] = $pDriver;
                }
            }
        }
        return $this->create($fields, isset($attributes['draftId']) ? $attributes['draftId'] : null);
    }

    public function update($id, $data)
    {
        return $this->policyRepository->update($id, $data);
    }

    public function getNotPaidPolicyByPaymentNumber($policyNumber)
    {
        return $this->policyRepository->getNotPaidPolicyByPaymentNumber($policyNumber);
    }

    public function getNotPaidPolicies($limit)
    {
        return $this->policyRepository->getNotPaidPolicies($limit);
    }

    public function searchOldPolicyByPolicyNumber($companyId, $attributes)
    {
        if (!isset($attributes['number'])) {
            return false;
        }
        $policy = $this->policyRepository->searchOldPolicyByPolicyNumber($companyId, $attributes['number']);
        if (!$policy) {
            return false;
        }
        return $policy->number;
    }

}
