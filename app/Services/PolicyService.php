<?php

namespace App\Services;

use App\Contracts\Repositories\DraftRepositoryContract;
use App\Contracts\Repositories\PolicyRepositoryContract;
use App\Contracts\Repositories\Services\DocTypeServiceContract;
use App\Contracts\Repositories\Services\PolicyTypeServiceContract;
use App\Contracts\Services\PolicyServiceContract;
use App\Exceptions\ApiRequestsException;
use App\Exceptions\StatisticsNotFoundException;
use App\Repositories\PolicyRepository;
use App\Traits\ValueSetterTrait;
use Benfin\Api\Contracts\AuthMicroserviceContract;
use Benfin\Api\Contracts\CommissionCalculationMicroserviceContract;
use Benfin\Api\GlobalStorage;
use Benfin\Api\Services\AuthMicroservice;
use Benfin\Api\Services\CommissionCalculationMicroservice;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Carbon\CarbonPeriod;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Arr;

class PolicyService implements PolicyServiceContract
{
    use ValueSetterTrait;

    /** @var PolicyRepository $policyRepository */
    private $policyRepository;
    /** @var CommissionCalculationMicroservice */
    private $commissionCalculationService;
    /** @var AuthMicroservice */
    private $authService;

    public function __construct(PolicyRepositoryContract $policyRepository)
    {
        $this->policyRepository = $policyRepository;
        $this->commissionCalculationService = app(CommissionCalculationMicroserviceContract::class);
        $this->authService = app(AuthMicroserviceContract::class);
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
            if (empty($agentIds) && empty($clientIds)) {
                return [
                    'policy' => [],
                    'pagination' => [
                        'pageCount' => 0
                    ]
                ];
            }
            $filter['agent_ids'] = array_merge($filter['agent_ids'] ?? [], $agentIds);

            $filter['client_ids'] = $clientIds;
        }

        $policies = $this->policyRepository->getList($filter);
        if ($policies->isNotEmpty()) {
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
            $rewards = collect(
                Arr::get(app(CommissionCalculationMicroserviceContract::class)->getRewards(['policy_id' => $policyIds] ?? []), 'content')
            )->mapToGroups(function ($reward) {
                return [$reward['policy_id'] => $reward];
            });

            $policies = $policies->map(function ($policy) use ($agents, $clients, $rewards) {
                $policy['type'] = $policy->type->name;
                $policy['company'] = $policy->company->name;
                $policy['referer'] = $policy->agent_id !== GlobalStorage::getUserId();
                $policy['agent'] = $agents[$policy->agent_id]['full_name'] ?? '';
                $policy['client'] = $clients[$policy->client_id]['full_name'] ?? '';
                $policy['insurant'] = $clients[$policy->insurant_id]['full_name'] ?? '';
                $policy['rewards'] = $rewards[$policy->id] ?? [];
                $reward = $this->getCommission($rewards[$policy->id] ?? []);
                $policy['commission_paid'] = $reward['paid'] ?? false;
                $policy['commission_value'] = $reward['value'] ?? null;

                return $policy;
            });

            if (isset($filter['commission_paid'])) {
                $paid = $filter['commission_paid'];
                $policies = $policies->filter(function ($policy) use ($paid) {
                    return $policy['commission_paid'] == $paid;
                });
            }

            if (isset($filter['referer'])) {
                $referer = $filter['referer'];
                $policies = $policies->filter(function ($policy) use ($referer) {
                    return $policy['referer'] == $referer;
                });
            }

            if ($order === 'desc') {
                $policies = $policies->sortByDesc($sort);
            } else {
                $policies = $policies->sortBy($sort);
            }
            /*
             * костыль, что бы сохранить сортировку,
             * так как sortBy не меняет ключи, а response->json() сортирует по ключам
             */
            $policies = collect($policies->values());
        }

        return [
            'policy' => $policies->forPage($page, $perPage),
            'pagination' => [
                'pageCount' => ceil($policies->count() / $perPage)
            ]
        ];
    }

    private function getCommission($rewards)
    {
        $reward = collect($rewards)->first(function ($reward) {
            return $reward['user_id'] === GlobalStorage::getUserId();
        });

        return $reward;
    }

    /**возвращает список полисов и вознаграждений по фильтру
     * @param array $filter
     * @return array
     * @throws ApiRequestsException
     */
    public function listWithRewards(array $filter)
    {
        $filter['agent_id'] = GlobalStorage::getUserId();
        $page = $filter['page'] ?? 1;
        $per_page = $filter['per_page'] ?? 10;
        $sort = $filter['sort'] ?? 'id';
        $order = $filter['order'] ?? 'asc';

        //получаем субагентов
        $subagents = $this->authService->getSubagents();
        if ($subagents['error']) {
            throw new ApiRequestsException($subagents['errors']);
        }
        $subagents = $subagents['content']['subagents'];
        $subagents_ids = [];
        foreach ($subagents as $subagent) {
            $subagents_ids[] = $subagent['id'];
        }

        //получаем вознаграждения
        $rewards = $this->commissionCalculationService->getRewards(['paid' => $filter['reward_paid'], 'user_id' => $filter['agent_id']]);
        if ($rewards['error']) {
            throw new ApiRequestsException($rewards['errors']);
        }
        $rewards = $rewards['content'];
        $policies_ids = [];
        foreach ($rewards as $reward) {
            $policies_ids[] = $reward['policy_id'];
        }

        //получаем полисы по вознаграждениям
        /** @var Collection $policies */
        $policies = $this->policyRepository->getList([
            'from' => $filter['from'] ?? '',
            'to' => $filter['to'] ?? '',
            'paid' => $filter['police_paid'] ?? true,
            'ids' => $policies_ids,
            'agent_ids' => array_merge($subagents_ids, [$filter['agent_id']]),
        ]);
        $clients_ids = [];
        foreach ($policies as $police) {
            $clients_ids[] = $police['client_id'];
        }

        //получаем пользователей
        $clients = $this->commissionCalculationService->getClients(['client_id' => array_unique($clients_ids)]);
        if ($clients['error']) {
            throw new ApiRequestsException($clients['errors']);
        }
        $clients = $clients['content'];

        //объединяем результат
        $result = [];
        foreach ($policies as $police) {
            $item = $police->toArray();
            foreach ($clients as $client) {
                if ($client['id'] == $item['client_id']) {
                    $item['client'] = $client;
                }
            }
            foreach ($rewards as $reward) {
                if ($item['id'] == $reward['policy_id']) {
                    $item['rewards'][] = $reward;
                }
            }
            $result[] = $item;
        }

        //обработка результата
        $collection = new Collection($result);

        //сортировка
        if ($order === 'desc') {
            $collection = $collection->sortByDesc($sort);
        } else {
            $collection = $collection->sortBy($sort);
        }

        //собраем массив, чтоб не потерять сортировку при преобразовании в json
        $data = [];
        foreach ($collection as $item) {
            $data[] = $item;
        }

        //пагинация
        return [
            'page' => $page,
            'per_page' => $per_page,
            'order' => $order,
            'sort' => $sort,
            'total_pages' => ceil(($collection->count() / $per_page)),
            'data' => $data,
        ];
    }

    private function getSearchAgentIds(string $search)
    {
        $mks = app(AuthMicroserviceContract::class);

        $result = $mks->search($search);

        return array_values(Arr::get($result, 'content', []));
    }

    private function getSearchClientIds(string $search)
    {
        $mks = app(CommissionCalculationMicroserviceContract::class);

        $result = $mks->search($search);

        return array_values(Arr::get($result, 'content', []));
    }

    public function create(array $fields, int $draftId = null)
    {
        $fields['region_kladr'] = $fields['subjects'][$fields['insurant_id']]['region_kladr'];
        $policy = $this->policyRepository->create($fields);

        /**
         * @var CommissionCalculationMicroservice $mks
         */
        $mks = app(CommissionCalculationMicroserviceContract::class);
        $owner = $fields['subjects'][$policy->client_id];
        unset($owner['id']);
        $owner['agent_id'] = GlobalStorage::getUserId();
        $owner_id = $mks->createClient($owner);
        $policy->client_id = Arr::get($owner_id, 'content.id');
        if (count($fields['subjects']) > 1) {
            $insurant = $fields['subjects'][$policy->insurant_id];
            unset($insurant['id']);
            $insurant['agent_id'] = GlobalStorage::getUserId();
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
        $reward = $mks->createRewards($policy->id, $policy->premium, $policy->registration_date->format('Y-m-d'), $policy->region_kladr, GlobalStorage::getUserId());
        if (
            (isset($reward['error']) && !$reward['error']) &&
            (isset($reward['content']) && isset($reward['content']['reward_id']))
        ) {
            $policy->commission_id = $reward['content']['reward_id'];
            $policy->save();
        }

        return $policy->id;
    }

    /**возарщает статистику полисов для агентов
     * @param array $filter
     * @return array
     * @throws StatisticsNotFoundException
     */
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

        /** * @var Collection $policies */
        $policies = $this->policyRepository->getList($filter)->sortBy('registration_date');

        if ($policies->isNotEmpty()) {
            $startDate = Carbon::parse($filter["from"]);
            $endDate = Carbon::parse($filter["to"]);
            $needSortByMonth = $startDate->diffInMonths($endDate) > 0;

            $organizedPolicies = $this->organizePolicies($policies, $userId, $subagentIds);
            $organizedStatistics = $this->makeStatistic($organizedPolicies, $needSortByMonth, $filter['from'], $filter['to']);
        }

        if (empty($organizedStatistics)) {
            throw new StatisticsNotFoundException('За выбранный период не продано ни одного полиса');
        }
        return $organizedStatistics;
    }

    /**
     * @param $policies
     * сортировка массива по своим/агентским продажам
     * @param $userId
     * @param array $subagent_ids
     * @return array
     */
    private function organizePolicies($policies, $userId, $subagent_ids = [])
    {
        $organized = [];

        foreach ($policies as $policy) {
            if ($policy->agent_id == $userId) {
                $organized["self"][] = $policy;
            } else {
                if (!empty($subagent_ids) && in_array($policy->agent_id, $subagent_ids)) {
                    $organized["network"][] = $policy;
                }
            }
            $organized["all"][] = $policy;
        }

        return $organized;
    }

    /**
     * @param array $organizedArray
     * @param bool $needSortByMonth
     * @param $from
     * @param $to
     * @return array
     */
    private function makeStatistic($organizedArray, $needSortByMonth, $from, $to)
    {
        $statistics = [];
        foreach ($organizedArray as $key => $policies) {
            $statistics[$key] = $this->makeStatisticFromPoliciesList($policies, $needSortByMonth, $from, $to);
        }
        return $statistics;
    }

    /**
     * Группировка статистики продаж по датам
     * @param Collection $policiesList
     * @param bool $needSortByMonth
     * @param $from
     * @param $to
     * @return array
     */
    private function makeStatisticFromPoliciesList($policiesList, $needSortByMonth, $from, $to): array
    {
        //группировка по страховым компаниям
        $policiesList = collect($policiesList);
        $byInsuranceCompany = $policiesList
            ->groupBy('insurance_company_id')
            ->map(function ($item, $index) {
                $tmp = collect($item);
                return [
                    "count" => $tmp->count(),
                    "sum" => $tmp->sum("premium")
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
                    $d = Carbon::parse($item['registration_date']);
                    return $d->year . " " . $d->month;
                })
                ->map(function ($list, $index) {
                    $tmp = collect($list);
                    $d = Carbon::parse($tmp->first()->registration_date);
                    return [
                        "count" => $tmp->count(),
                        "sum" => $tmp->sum("premium"),
                        "label" => mb_convert_case($d->locale('ru')->getTranslatedMonthName('F') . " " . $d->year, MB_CASE_TITLE),
                    ];
                });

            //добавление несуществующих в выборке дат
            $period = CarbonPeriod::create($from, '1 months', $to)->locale('ru');
            foreach ($period as $date) {
                /** @var CarbonInterface $date */
                $formatted = $date->year . " " . $date->month;
                if (!$result['detail']->has($formatted)) {
                    $result['detail']->put($formatted,
                        [
                            'count' => 0,
                            'sum' => 0,
                            'label' => mb_convert_case($date->locale('ru')->getTranslatedMonthName('F') . " " . $date->year, MB_CASE_TITLE),
                        ]
                    );
                }
            }
        } else {
            $result["detail"] = $policiesList
                ->groupBy('registration_date')
                ->map(function ($list, $index) {
                    $tmp = collect($list);
                    $d = Carbon::parse($tmp->first()->registration_date);
                    return [
                        "count" => $tmp->count(),
                        "sum" => $tmp->sum("premium"),
                        'label' => $d->format('d.m.Y'),
                    ];
                });
            $period = CarbonPeriod::create($from, $to)->locale('ru');

            //добавление несуществующих в выборке дат
            foreach ($period as $date) {
                /** @var CarbonInterface $date */
                $formatted = $date->format("Y-m-d");
                if (!$result['detail']->has($formatted)) {
                    $result['detail']->put($formatted,
                        [
                            'count' => 0,
                            'sum' => 0,
                            'label' => $date->format('d.m.Y'),
                        ]);
                }
            }

            //сортировка по датам
            $result['detail'] = $result['detail']->sortKeys();
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
        $policyTypeService = app(PolicyTypeServiceContract::class);
        $fields['type_id'] = $policyTypeService->getByCode('osago')->id; // this field should be received from form, but this feature not realised at 1st step of development
        if (isset($attributes['number'])) {
            $fields['number'] = $attributes['number'];
        }
        $fields['premium'] = $attributes['premium'];
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
                'gender_id' => 'gender',
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
            'vehicle_with_trailer' => 'isUsedWithTrailer',
            'vehicle_mileage' => 'mileage',
            'vehicle_acquisition' => 'sourceAcquisition',
            'vehicle_cost' => 'vehicleCost',
            'vehicle_usage_target' => 'vehicleUsage',
            'vehicle_vin' => 'vin',
            'vehicle_engine_power' => 'enginePower',
            'vehicle_reg_number' => 'regNumber',
            'vehicle_made_year' => 'year',
            'vehicle_unladen_mass' => 'minWeight',
            'vehicle_loaded_mass' => 'maxWeight',
            'vehicle_count_seats' => 'seats',
        ], $attributes['car']);
        $docTypeService = app(DocTypeServiceContract::class);
        $docTypeId = $docTypeService->getDocTypeByCode($attributes['car']['document']['documentType']);
        $fields['vehicle_reg_doc_type_id'] = $docTypeId;
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

        if (isset($attributes['drivers']) && $attributes['drivers']) {
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
