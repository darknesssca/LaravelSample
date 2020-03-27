<?php

namespace App\Services;

use App\Contracts\Services\PolicyServiceContract;
use App\Models\Policy;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;

class PolicyService implements PolicyServiceContract
{
    private const STATUS_ISSUED = 2;

    public function getList(array $filter = [])
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

        if (isset($filter['commission_paid'])) {
            $query = $query->where('commission_paid', $filter['commission_paid']);
        }

        return $query->get();
    }

    public function getById($id)
    {
        return Policy::findOrFail($id);
    }

    public function create(array $fields, int $draftId = null)
    {
        $policy = new Policy();
        try {
            $policy->fill([
                'agent_id' => 1,
                'status_id' => self::STATUS_ISSUED,
            ])->saveOrFail();

            $policy->drivers()->create([]);
        } catch (\Throwable $e) {
            dd($e->getMessage());
        }
    }
}
