<?php



namespace App\Repositories;


use App\Contracts\Repositories\PolicyRepositoryContract;
use App\Models\Policy;
use Carbon\Carbon;

class PolicyRepository implements PolicyRepositoryContract
{

    public function getList(array $filter)
    {
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
            $query = $query->whereIn('client_id', $clientIds);
        }

        if ($companyIds = $filter['company_ids'] ?? null) {
            $query = $query->whereIn('company_id', $companyIds);
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
    }

    public function create(array $data)
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
        return Policy::where('id', $id)->update($data);
    }

    public function searchOldPolicyByPolicyNumber($companyId, $policyNumber)
    {
        return Policy::where('paid', 1)
            ->where('insurance_company_id', $companyId)
            ->where('number', $policyNumber)
            ->first();
    }
}
