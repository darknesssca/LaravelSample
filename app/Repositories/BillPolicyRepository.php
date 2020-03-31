<?php


namespace App\Repositories;


use App\Contracts\Repositories\BillPolicyRepositoryContract;
use App\Models\BillPolicy;

class BillPolicyRepository implements BillPolicyRepositoryContract
{
    public function delete($policyId)
    {
        return BillPolicy::where('policy_id', $policyId)->delete();
    }
}
