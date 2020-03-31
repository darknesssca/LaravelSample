<?php

namespace App\Services;

use App\Contracts\Repositories\DraftRepositoryContract;
use App\Contracts\Repositories\PolicyRepositoryContract;
use App\Contracts\Services\PolicyServiceContract;
use Carbon\Carbon;

class PolicyService implements PolicyServiceContract
{
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
}
