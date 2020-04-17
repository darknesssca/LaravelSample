<?php

namespace App\Services;

use App\Contracts\Repositories\DraftRepositoryContract;
use App\Contracts\Repositories\PolicyRepositoryContract;
use App\Contracts\Services\PolicyServiceContract;
use App\Exceptions\StatisticsNotFoundException;
use App\Traits\ValueSetterTrait;
use Benfin\Api\Contracts\AuthMicroserviceContract;
use Benfin\Api\Contracts\CommissionCalculationMicroserviceContract;
use Benfin\Api\GlobalStorage;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Arr;

class PolicyService implements PolicyServiceContract
{
    use ValueSetterTrait;

    private $policyRepository;

    public function __construct(PolicyRepositoryContract $policyRepository)
    {
        $this->policyRepository = $policyRepository;
    }

    public function getList(array $filter = [], string $sort = 'id', string $order = 'asc', int $page = 1, int $perPage = 20, string $search = null)
    {
        $isAdmin = in_array('admin', GlobalStorage::getUserGroup());
        if (!$isAdmin) {
            $filter['agent_ids'] = [GlobalStorage::getUserId()];

            $subagents = Arr::get(app(AuthMicroserviceContract::class)->getSubagents(GlobalStorage::getUserId()), 'content.subagents', []);
            foreach ($subagents as $subagent) {
                $filter['agent_ids'][] = $subagent['id'];
            }
        }

        if ($search) {
            $agentIds = $this->getSearchAgentIds($search) ?? [];
            $clientIds = $this->getSearchClientIds($search) ?? [];
            $filter['agent_ids'] = array_merge($filter['agent_ids'] ?? [], $agentIds);

            $filter['client_ids'] = $clientIds;
        }

        $policies = $this->policyRepository->getList($filter);
        $policyIds = [];
        $clientIds = [];
        $agentIds = [];

        foreach ($policies as $policy) {
            $agentIds[] = $policy->agent_id;
            $clientIds[] = $policy->client_id;
            $clientIds[] = $policy->insurant_id;
            $policyIds[] = $policy->id;
        }

        $agents = app(AuthMicroserviceContract::class)->usersInfo($agentIds) ?? [];
        $clients = app(CommissionCalculationMicroserviceContract::class)->clientsInfo($clientIds) ?? [];
        $rewards = collect(app(CommissionCalculationMicroserviceContract::class)
            ->getRewards(['policy_id' => $policyIds] ?? [])
        )->mapToGroups(function ($reward) {
            return [$reward['policy_id'] => $reward];
        });

        $policies = $policies->map(function ($policy) use($agents, $clients, $rewards) {
            $policy['type'] = $policy->type->name;
            $policy['company'] = $policy->company->name;
            $policy['referer'] = $policy->agent_id !== GlobalStorage::getUserId();
            $policy['agent'] = $agents[$policy->agent_id]['full_name'] ?? '';
            $policy['client'] = $clients[$policy->client_id]['full_name'] ?? '';
            $policy['insurant'] = $clients[$policy->insurant_id]['full_name'] ?? '';
            $policy['rewards'] = $rewards[$policy->id] ?? [];
        });

        if ($order === 'desc') {
            $policies = $policies->sortByDesc($sort);
        } else {
            $policies = $policies->sortBy($sort);
        }

        return $policies->forPage($page, $perPage);
    }

    private function getSearchAgentIds(string $search)
    {
        $mks = app(AuthMicroserviceContract::class);

        $result = $mks->search($search);

        return array_values(Arr::get($result, 'content'));
    }

    private function getSearchClientIds(string $search)
    {
        $mks = app(CommissionCalculationMicroserviceContract::class);

        $result = $mks->search($search);

        return array_values(Arr::get($result, 'content'));
    }

    public function create(array $fields, int $draftId = null)
    {
        $fields['region_kladr'] = $fields['subjects'][$fields['insurant_id']]['region_kladr'];
        $policy = $this->policyRepository->create($fields);

        /**
         * @var CommissionCalculationMicroserviceContract $mks
         */
        $mks = app(CommissionCalculationMicroserviceContract::class);

        $owner = $fields['subjects'][$policy->client_id];
        unset($owner['id']);
        $owner_id = $mks->createClient($owner);
        $policy->client_id = Arr::get($owner_id, 'content.id');

        if (count($fields['subjects']) > 1) {
            $insurant = $fields['subjects'][$policy->insurant_id];
            unset($insurant['id']);
            $insurant_id = $mks->createClient($insurant);
            $policy->insurant_id = Arr::get($insurant_id, 'content.id');;
        }

        $policy->save();

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

        if ($draftId) {
            app(DraftRepositoryContract::class)->delete($draftId);
        }

        $mks->createRewards($policy->id, $policy->registration_date->format('Y.m.d'), $policy->region_kladr, GlobalStorage::getUserId());

        return $policy->id;
    }

    public function statistic(array $filter = [])
    {
        $userId = GlobalStorage::getUserId();
        $subagentIds = [];

        //Если не админ, то заполняем фильтр по id пользователя и субагентов(если они есть)
        if (!GlobalStorage::userIsAdmin()) {
            $userWithSubagents = Arr::get(app(AuthMicroserviceContract::class)->getSubagents(), 'content');
            $filter['agent_ids'] = [$userId];
            if (isset($userWithSubagents["subagents"]) && !empty($userWithSubagents["subagents"])) {
                foreach ($userWithSubagents["subagents"] as $subagent) {
                    $subagentIds[] = $subagent["id"];
                }
                $filter['agent_ids'] = array_merge($filter['agent_ids'], $subagentIds);
            }
        }

        /**
         * @var Collection $policies
         */
        $policies = $this->policyRepository->getList($filter)->sortBy('registration_date');

        if ($policies->isNotEmpty()) {
            $startDate = Carbon::parse($filter["from"]);
            $endDate = Carbon::parse($filter["to"]);
            $needSortByMonth = $startDate->diffInMonths($endDate) > 0;

            $organizedPolicies = $this->organizePolicies($policies, $userId, $subagentIds);
            $organizedStatistics = $this->makeStatistic($organizedPolicies, $needSortByMonth);
        }

        if (empty($organizedStatistics)) {
            throw new StatisticsNotFoundException('За выбранный период не продано ни одного полиса');
        }
        return $organizedStatistics;
    }

    /**
     * @param $policies
     * сортировка массива по своим/агентским продажам
     */
    private function organizePolicies($policies, $userId, $subagent_ids = [])
    {
        $organized = [];

        foreach ($policies as $policy) {
            if ($policy->agent_id == $userId) {
                $organized["self"][] = $policy;
            } else if (!empty($subagent_ids) && in_array($policy->agent_id, $subagent_ids)) {
                $organized["network"][] = $policy;
            }
            $organized["all"][] = $policy;
        }

        return $organized;
    }

    /**
     * @param array $organizedArray
     * @param bool $needSortByMonth
     *
     */
    private function makeStatistic($organizedArray, $needSortByMonth)
    {
        $statistics = [];
        foreach ($organizedArray as $key => $policies) {
            $statistics[$key] = $this->makeStatisticFromPoliciesList($policies, $needSortByMonth);
        }
        return $statistics;
    }

    /**
     * @param Collection $policiesList
     * @param bool $needSortByMonth
     * @return array
     * Группировка статистики продаж по датам
     */
    private function makeStatisticFromPoliciesList($policiesList, $needSortByMonth): array
    {
        $policiesList = collect($policiesList);
        $byInsuranceCompany = $policiesList
            ->groupBy('insurance_company_id')
            ->map(function($item, $index){
                $tmp = collect($item);

                return [
                    "count" => $tmp->count(),
                    "sum"   => $tmp->sum("premium")
                ];
            });
        $result = [
            "count" => $policiesList->count(),
            "sum" => $policiesList->sum('premium'),
            "by_insurance_company" => $byInsuranceCompany
        ];

        if ($needSortByMonth) {
            $result["detail"] = $policiesList
                ->groupBy(function ($item, $index) {
                    return Carbon::parse($item['registration_date'])->locale('ru')->getTranslatedMonthName('MMMM YYYY');
                })
                ->map(function ($list, $index) {
                    $tmp = collect($list);

                    return [
                        "count" => $tmp->count(),
                        "sum"   => $tmp->sum("premium")
                    ];
                });
        } else {
            $result["detail"] = $policiesList
                ->groupBy('registration_date')
                ->map(function($list, $index) {
                    $tmp = collect($list);

                    return [
                        "count" => $tmp->count(),
                        "sum"   => $tmp->sum("premium")
                    ];
                });
        }
        return $result;
    }

    public function createPolicyFromCustomData($companyId, $attributes)
    {
        $fields = [
            'agent_id' => GlobalStorage::getUserId(),
            'insurance_company_id' => $companyId,
            'subjects' => [],
            'drivers' => [],
        ];
        if (isset($attributes['number'])) {
            $fields['number'] = $attributes['number'];
        }
        foreach ($attributes['subjects'] as $subject) {
            $pSubject = [
                'id' => $subject['id'],
            ];
            $this->setValuesByArray($pSubject, [
                'last_name' => 'lastName',
                'first_name' => 'firstName',
                'birth_date' => 'birthdate',
                'patronymic' => 'middleName',
                'email' => 'email',
                'gender' => 'gender',
                'citizenship' => 'citizenship',
                'phone' => 'phone',
            ], $subject['fields']);
            foreach ($subject['fields']['addresses'] as $address) {
                if ($address['address']['addressType'] == 'registration') {
                    $pSubject['address'] = $address['address']['city'] . ' ' . $address['address']['street'] .
                        ' ' . $address['address']['building'] . $address['address']['flat'];

                    $pSubject['region_kladr'] = $address['address']['regionKladr'];
                }
            }
            foreach ($subject['fields']['documents'] as $document) {
                if ($document['document']['documentType'] == 'passport') {
                    $this->setValuesByArray($pSubject, [
                        'is_russian' => 'isRussian',
                        'passport_series' => 'series',
                        'passport_number' => 'number',
                        'passport_issuer' => 'issuedBy',
                        'passport_date' => 'dateIssue',
                    ], $document['document']);
                }
            }
            $fields['subjects'][$pSubject['id']] = $pSubject;
        }
        $this->setValuesByArray($fields, [
            'vehicle_model' => 'model',
            'maker' => 'maker',
            'vehicle_reg_country' => 'countryOfRegistration',
            'isUsedWithTrailer' => 'isUsedWithTrailer',
            'mileage' => 'mileage',
            'sourceAcquisition' => 'sourceAcquisition',
            'vehicleCost' => 'vehicleCost',
            'vehicleUsage' => 'vehicleUsage',
            'vehicle_vin' => 'vin',
            'regNumber' => 'regNumber',
            'vehicle_made_year' => 'year',
            'minWeight' => 'minWeight',
            'maxWeight' => 'maxWeight',
            'vehicle_count_seats' => 'seats',
        ], $attributes['car']);

        $this->setValuesByArray($fields, [
            'vehicle_doc_series' => 'series',
            'vehicle_doc_number' => 'number',
            'vehicle_doc_issued' => 'dateIssue',
        ], $attributes['car']['document']);

        $this->setValuesByArray($fields, [
            'vehicle_inspection_doc_series' => 'series',
            'vehicle_inspection_doc_number' => 'number',
            'vehicle_inspection_issued_date' => 'dateIssue',
            'vehicle_inspection_end_date' => 'dateEnd',
        ], $attributes['car']['inspection']);

        $this->setValuesByArray($fields, [
            'start_date' => 'beginDate',
            'end_date' => 'endDate',
            'client_id' => 'ownerId',
            'insurant_id' => 'insurantId',
            'is_multi_drive' => 'isMultidrive'
        ], $attributes['policy']);

        foreach ($attributes['drivers'] as $driver) {
            foreach ($attributes['subjects'] as $subject) {
                if ($subject['id'] == $driver['driver']['driverId']) {
                    $pDriver = [];
                    $this->setValuesByArray($pDriver, [
                        'last_name' => 'lastName',
                        'first_name' => 'firstName',
                        'birth_date' => 'birthdate',
                    ], $subject['fields']);
                    $this->setValuesByArray($pDriver, [
                        'exp_start_date' => 'drivingLicenseIssueDateOriginal',
                    ], $driver['driver']);
                    foreach ($subject['fields']['documents'] as $document) {
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
