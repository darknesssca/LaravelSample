<?php


namespace App\Repositories;


use App\Cache\Policy\PolicyCacheTag;
use App\Contracts\Repositories\PolicyRepositoryContract;
use App\Models\Policy;
use Benfin\Api\GlobalStorage;
use Benfin\Cache\CacheKeysTrait;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;

class PolicyRepository implements PolicyRepositoryContract
{
    use PolicyCacheTag, CacheKeysTrait;

    private $CACHE_DAY_TTL = 24 * 60 * 60;

    public function getList(array $filter)
    {
        //Оборачиваем запрос в аннонимную функцию, для кеша или для вызова по условию
        $queryFunction = function () use ($filter) {
            $query = Policy::query()->with('type');

            if ($policyIds = $filter['policy_ids'] ?? null) {
                $query = $query->whereIn('id', $policyIds);
            }

            if ($agentIds = $filter['agent_ids'] ?? null) {
                $query = $query->whereIn('agent_id', $agentIds);
            }

            if ($policeIds = $filter['ids'] ?? null) {
                $query = $query->whereIn('id', $policeIds);
            }

            if ($clientIds = $filter['client_ids'] ?? null) {
                if (!empty($filter['agent_ids'])) {
                    $query = $query->orWhereIn('client_id', $clientIds);
                } else {
                    $query = $query->whereIn('client_id', $clientIds);
                }
            }

            if ($companyIds = $filter['company_ids'] ?? null) {
                $query = $query->whereIn('insurance_company_id', $companyIds);
            }

            if (isset($filter['paid'])) {
                $query = $query->where('paid', $filter['paid']);
            }
            if ($from = $filter['from'] ?? null) {
                $query = $query->where('registration_date', '>=', Carbon::parse($from));
            }

            if ($to = $filter['to'] ?? null) {
                $query = $query->where('registration_date', '<=', Carbon::parse($to));
            }

            return $query->get();
        };
        //Если пользователь  админ выдаем данные без кеша
        if (GlobalStorage::userIsAdmin()) {
            return $queryFunction();
        }

        $cacheTag = self::getPolicyListCacheTagByUser();
        $cacheKey = self::getCacheKey($filter);

        return Cache::tags($cacheTag)->remember($cacheKey, $this->CACHE_DAY_TTL, $queryFunction);
    }

    /**Создает и сохраняет новый полис в БД
     * @param array $data
     * @return Policy
     * @throws \Throwable
     */
    public function create(array $data): Policy
    {
        $policy = new Policy();
        $policy->fill($data);
        $policy->registration_date = Carbon::now();

        $policy->saveOrFail();

        return $policy;
    }

    public function getNotPaidPolicyByPaymentNumber($policyNumber)
    {
        return Policy::where('number', $policyNumber)
            ->where('paid', 0)
            ->first();
    }

    public function getNotPaidPolicies($limit)
    {
        return Policy::with([
            'company',
            'bill',
        ])
            ->where('paid', 0)
            ->whereDate('registration_date', '>', (new Carbon)->subDays(2)->format('Y-m-d'))
            ->limit($limit)
            ->first();
    }

    public function update($id, $data)
    {
        return Policy::find($id)->update($data);
    }

    public function searchOldPolicyByPolicyNumber($companyId, $policyNumber)
    {
        return Policy::where('paid', 1)
            ->where('insurance_company_id', $companyId)
            ->where('number', $policyNumber)
            ->first();
    }
}
