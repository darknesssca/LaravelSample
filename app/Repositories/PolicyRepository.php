<?php

namespace App\Repositories;

use App\Contracts\Repositories\PolicyRepositoryContract;
use App\Models\Policy;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

class PolicyRepository implements PolicyRepositoryContract
{

    public function getList(array $filter)
    {
        $query = Policy::query();

        if ($agentIds = $filter['agent_ids'] ?? null) {
            $query = $query->whereIn('agent_id', $agentIds);
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
}
