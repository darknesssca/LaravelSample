<?php

namespace App\Services;

use App\Contracts\Repositories\DraftRepositoryContract;
use App\Contracts\Repositories\PolicyRepositoryContract;
use App\Contracts\Repositories\Services\DocTypeServiceContract;
use App\Contracts\Repositories\Services\PolicyTypeServiceContract;
use App\Contracts\Services\PolicyServiceContract;
use App\Contracts\Services\ReportServiceContract;
use App\Exceptions\ApiRequestsException;
use App\Exceptions\PolicyNotFoundException;
use App\Exceptions\StatisticsNotFoundException;
use App\Models\Policy;
use App\Models\Report;
use App\Repositories\PolicyRepository;
use App\Services\Qiwi\ReportService;
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
    /**  @var ReportService  */
    private $reportService;

    public function __construct(PolicyRepositoryContract $policyRepository)
    {
        $this->policyRepository = $policyRepository;
        $this->commissionCalculationService = app(CommissionCalculationMicroserviceContract::class);
        $this->authService = app(AuthMicroserviceContract::class);
        $this->reportService = app(ReportServiceContract::class);
    }

    public function getList(array $filter = [], string $sort = 'id', string $order = 'asc', int $page = 1, int $perPage = 20, string $search = null)
    {
        $isAdmin = GlobalStorage::userIsAdmin();
        if (!$isAdmin) {
            $filter['agent_ids'] = [GlobalStorage::getUserId()];

            $subagents = Arr::get(app(AuthMicroserviceContract::class)->getSubagents(), 'content.subagents', []);
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

            $filter['agent_ids'] = $isAdmin ? $agentIds : array_intersect($filter['agent_ids'] ?? [], $agentIds);

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
            $agentIds = array_unique($agentIds);
            $clientIds = array_unique($clientIds);
            $policyIds = array_unique($policyIds);

            $agents = app(AuthMicroserviceContract::class)->usersInfo($agentIds) ?? [];
            $clients = app(CommissionCalculationMicroserviceContract::class)->clientsInfo($clientIds) ?? [];
            $rewards = collect(
                Arr::get(app(CommissionCalculationMicroserviceContract::class)->getRewards(['policy_id' => $policyIds] ?? []), 'content')
            )->mapToGroups(function ($reward) {
                return [$reward['policy_id'] => $reward];
            });

            $policies = $policies->map(function ($policy) use ($agents, $clients, $rewards, $isAdmin) {
                $policy['type'] = $policy->type->name;
                $policy['company'] = $policy->company->name;
                $policy['referer'] = $policy->agent_id !== GlobalStorage::getUserId();
                $policy['agent'] = $agents[$policy->agent_id]['full_name'] ?? '';
                $policy['client'] = $clients[$policy->client_id]['full_name'] ?? '';
                $policy['insurant'] = $clients[$policy->insurant_id]['full_name'] ?? '';
                $policy['rewards'] = $rewards[$policy->id] ?? [];
                $reward = $this->getRewardValue($rewards[$policy->id] ?? [], $policy->agent_id);
                $policy['commission_paid'] = $reward['paid'] ?? false;
                $policy['commission_value'] = $reward['value'] ?? null;
                if ($isAdmin) {
                    $policy['agent_tax_status'] = $agents[$policy->agent_id]['tax_status']['name'] ?? '';
                    $policy['agent_inn'] = $agents[$policy->agent_id]['requisite']['inn'] ?? '';
                }

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
            // С помощью array values удаляем ключи чтобы вместо объекта был массив.
            //Если не удалить ключи то массив преобразуется в объект и на фронте работать не будет.
            'policy' => array_values($policies->forPage($page, $perPage)->toArray()),
            'pagination' => [
                'pageCount' => ceil($policies->count() / $perPage),
                'page' => $page,
                'per_page' => $perPage,
            ]
        ];
    }

    private function getRewardValue($rewards, $policy_agent_id)
    {
        $reward = collect($rewards)->first(function ($reward) use ($policy_agent_id) {
            if (GlobalStorage::userIsAdmin()) //для админа ищем основное вознаграждение агента
                return $reward['user_id'] === $policy_agent_id;
            else
                return $reward['user_id'] === GlobalStorage::getUserId();
        });

        return $reward;
    }

    /**возвращает список полисов, по которым пользователю можно получить выплату
     * (для этих полисов не должно быть создано отчетов у этого пользователя)
     * @param array $filter
     * @return array
     * @throws ApiRequestsException
     */
    public function listAbleToPayment(array $filter)
    {
        $filter['agent_id'] = GlobalStorage::getUserId();
        $page = $filter['page'] ?? 1;
        $per_page = $filter['per_page'] ?? 10;
        $sort = $filter['sort'] ?? 'id';
        $order = $filter['order'] ?? 'asc';

        //получаем список полисов, по которым уже есть отчет
        $exclude_policy_ids=$this->reportService->getReportedPoliciesIds($filter['agent_id']);

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

        $rewards_params['user_id'] = $filter['agent_id'];

        if (in_array(intval($filter['reward_paid']), [0, 1])){
            $rewards_params['paid'] = $filter['reward_paid'];
        }

        $rewards = $this->commissionCalculationService->getRewards($rewards_params);
        if ($rewards['error']) {
            throw new ApiRequestsException($rewards['errors']);
        }
        $rewards = $rewards['content'];
        $policies_ids = [];
        foreach ($rewards as $reward) {
            $policies_ids[] = $reward['policy_id'];
        }

        //получаем полисы по вознаграждениям
        $agents_ids=array_merge($subagents_ids, [$filter['agent_id']]);
        /** @var Collection $policies */
        $policies = $this->policyRepository->getList([
            'from' => $filter['from'] ?? '',
            'to' => $filter['to'] ?? '',
            'paid' => boolval($filter['police_paid']) ?? true,
            'ids' => $policies_ids, //берем только те, для которых есть вознаграждения
            'exclude_policy_ids' => $exclude_policy_ids, //исключаем те, у которых уже есть отчет для этого пользователя
            'agent_ids' => $agents_ids,
        ]);
        $clients_ids = [];
        foreach ($policies as $police) {
            $clients_ids[] = $police['client_id'];
        }

        //получаем клиентов
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
            $item['rewards'] = [];
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

        //пагинация
        $total_pages = ceil(($collection->count() / $per_page));
        $collection = $collection->forPage($page, $per_page);

        //собраем массив, чтоб не потерять сортировку при преобразовании в json
        $data = [];
        foreach ($collection as $item) {
            $data[] = $item;
        }

        return [
            'page' => $page,
            'per_page' => $per_page,
            'order' => $order,
            'sort' => $sort,
            'total_pages' => $total_pages,
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


        /**
         * @var CommissionCalculationMicroservice $mks
         */
        $mks = app(CommissionCalculationMicroserviceContract::class);
        $owner = $fields['subjects'][$fields['client_id']];
        $owner['agent_id'] = GlobalStorage::getUserId();
        $owner_id = $mks->createClient($owner);
        $fields['client_id'] = Arr::get($owner_id, 'content.id');

        $insurant = $fields['subjects'][$fields["insurant_id"]];
        $insurant['agent_id'] = GlobalStorage::getUserId();
        $insurant_id = $mks->createClient($insurant);
        $fields['insurant_id'] = Arr::get($insurant_id, 'content.id');

        $policy = $this->policyRepository->create($fields);

        if ($drivers = $fields['drivers']) {
            foreach ($drivers as &$driver) {
                if (isset($driver['birth_date']) && $driver['birth_date']) {
                    $driver['birth_date'] = Carbon::createFromFormat('Y-m-d', $driver['birth_date']);
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
            $draftRepository = app(DraftRepositoryContract::class);
            $draft = $draftRepository->getById($draftId, GlobalStorage::getUserId());
            if ($draft) {
                $draft->drivers()->delete();
                $draft->delete();
            }
        }

        $reward = $mks->createRewards($policy->id);

        if (
            (isset($reward['error']) && !$reward['error']) &&
            (isset($reward['content']) && isset($reward['content']['commission_id']))
        ) {
            $policy->commission_id = $reward['content']['commission_id'];
        }
        $policy->save();
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

        $user_ids = [];
        if ($policies->isNotEmpty()) {
            $ids = $policies->pluck('agent_id')->unique()->all();
            $user_ids = array_merge($user_ids, $ids);
        }

        if ($user_ids) {
            $usersInfo = app(AuthMicroserviceContract::class)->usersInfo($user_ids);
        } else {
            $usersInfo = [];
        }

        if ($policies->isNotEmpty()) {
            $organizedPolicies = $this->organizePolicies($policies, $userId, $subagentIds);

            $startDate = Carbon::parse($filter["from"]);
            $endDate = Carbon::parse($filter["to"]);
            $sort = [
                'needSortByMonth' => $startDate->diffInMonths($endDate) > 0,
                'from' => $filter['from'],
                'to' => $filter['to'],
                'groupedByUserOrderBy' => $filter['grouped_by_user_order_by'] ?? 'count',
            ];

            $organizedStatistics = $this->makeStatistic($organizedPolicies, $sort, $usersInfo);
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
            $policy->product_id = 1; //костыль, на данный момент отсутствует поле
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
     * @param $sort
     * @param $usersInfo
     * @return array
     */
    private function makeStatistic($organizedArray, $sort, $usersInfo)
    {
        $statistics = [];
        foreach ($organizedArray as $key => $policies) {
            $statistics[$key] = $this->makeStatisticFromPoliciesList($policies, $sort, $usersInfo);
        }
        return $statistics;
    }

    /**
     * Группировка статистики продаж по датам
     * @param Collection $policiesList
     * @param $sort
     * @param $usersInfo
     * @return array
     */
    private function makeStatisticFromPoliciesList($policiesList, $sort, $usersInfo): array
    {
        //группировка по страховым компаниям
        $policiesList = collect($policiesList);
        $result = [
            "count" => $policiesList->count(),
            "sum" => round($policiesList->sum('premium'), 2),
            "by_insurance_company" => $this->collectBy($policiesList, ['group_by' => 'insurance_company_id']),
            "by_user" => $this->collectByUser($policiesList, $sort['groupedByUserOrderBy']),
        ];

        $result['by_user'] = $result['by_user']->map(function ($user, $userId) use ($usersInfo) {
            return array_merge($user, [
                'first_name' => $usersInfo[$userId]['first_name'] ?? null,
                'last_name' => $usersInfo[$userId]['last_name'] ?? null,
                'patronymic' => $usersInfo[$userId]['patronymic'] ?? null,
                'tax_status' => $usersInfo[$userId]['tax_status']['name'] ?? null,
            ]);
        });

        if ($sort['needSortByMonth']) {
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
                        "sum" => round($tmp->sum("premium"), 2),
                        "label" => mb_convert_case($d->locale('ru')->getTranslatedMonthName('F') . " " . $d->year, MB_CASE_TITLE),
                    ];
                });

            //добавление несуществующих в выборке дат
            $period = CarbonPeriod::create($sort['from'], '1 months', $sort['to'])->locale('ru');
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
                        "sum" => round($tmp->sum("premium"), 2),
                        'label' => $d->format('d.m.Y'),
                    ];
                });
            $period = CarbonPeriod::create($sort['from'], $sort['to'])->locale('ru');

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
        }

        //сортировка по датам
        $result['detail'] = $result['detail']->sortKeys(SORT_NATURAL);


        return $result;
    }

    private function collectBy($policies, $struct)
    {
        $result = $policies
            ->groupBy($struct['group_by'])
            ->map(function ($groupedPolicies, $index) use ($struct) {
                $result = [
                    "count" => $groupedPolicies->count(),
                    "sum" => round($groupedPolicies->sum("premium"), 2)
                ];
                if (isset($struct['subGroup'])) {
                    $result[$struct['subGroup']['key']] = $this->collectBy($groupedPolicies, $struct['subGroup']);
                }
                return $result;
            });
        if (!empty($struct['order_by'])) {
            return $result->sortByDesc($struct['order_by']);
        }
        return $result;
    }

    private function collectByUser($policiesList, $orderBy)
    {
        $struct = [
            'group_by' => 'agent_id',
            'order_by' => $orderBy,
            'subGroup' => [
                'group_by' => 'insurance_company_id',
                'key' => 'by_insurance_company',
                'order_by' => $orderBy,
                'subGroup' => [
                    'group_by' => 'product_id',
                    'key' => 'by_product',
                    'order_by' => $orderBy,
                ],
            ],
        ];
        return $this->collectBy($policiesList, $struct);
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
        $fields["registration_date"] = Carbon::now()->format('Y-m-d');
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
                'citizenship_id' => 'citizenship',
                'phone' => 'phone',
                'birth_place' => 'birthPlace'
            ], $subject['fields']);
            foreach ($subject['fields']['addresses'] as $address) {
                if (isset($address['address']['addressType']) && $address['address']['addressType'] == 'registration') {
                    $cAddress = $address["address"];
                    $pSubject['address'] = '';
                    if (isset($cAddress['city']) && !empty($cAddress['city'])) {
                        $pSubject['address'] .= $cAddress['city'];
                    } else if (isset($cAddress['populatedCenter']) && !empty($cAddress['populatedCenter'])) {
                        $pSubject['address'] .= $cAddress['populatedCenter'];
                    }

                    $pSubject['address'] .= ', ' . $cAddress['street'] . ', д.' . $cAddress['building'];

                    if (isset($cAddress['flat']) && !empty($cAddress['flat'])) {
                        $pSubject['address'] .= ', кв.' . $cAddress['flat'];
                    }

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
                        'passport_unit_code' => 'subdivisionCode'
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
            'vehicle_usage_target' => 'vehicleUsage',
            'vehicle_vin' => 'vin',
            'vehicle_engine_power' => 'enginePower',
            'vehicle_reg_number' => 'regNumber',
            'vehicle_made_year' => 'year',
            'vehicle_unladen_mass' => 'minWeight',
            'vehicle_loaded_mass' => 'maxWeight',
            'vehicle_count_seats' => 'seats',
            'vehicle_category_id' => 'category',
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
                            'patronymic' => 'middleName',
                            'birth_date' => 'birthdate',
                            'citizenship_id' => 'citizenship',
                            'gender_id' => 'gender'
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

    public function getNotPaidPolicies($limit, $daysToSubtract = 2)
    {
        return $this->policyRepository->getNotPaidPolicies($limit, $daysToSubtract);
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

    /**
     * возвращает список пользователей, которые оформляли полисы
     * @param array $filter
     * @return array
     */
    public function usersWithPolicies(array $filter = [])
    {
        $agentList = $this->policyRepository->getUserListByPolicies($filter);

        if ($agentList->isNotEmpty()) {
            $ids = $agentList->pluck('agent_id')->all();
            return $this->authService->getUsersList(['user_id' => $ids]);
        }
        return [];
    }

    /**
     * получить полис по id
     * @param int $id
     * @return mixed
     * @throws PolicyNotFoundException
     */
    public function getById(int $id)
    {
        $policy = $this->policyRepository->getById($id);
        if (
            !$policy ||
            !GlobalStorage::userIsAdmin() && $policy->agent_id !== GlobalStorage::getUserId()
        ) {
            throw new PolicyNotFoundException('Полис не найден');
        }

        return $policy;
    }

}
