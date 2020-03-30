<?php


namespace App\Repositories;


use App\Contracts\Repositories\PolicyRepositoryContract;
use App\Models\Policy;

class PolicyRepository implements PolicyRepositoryContract
{
    public function getNotPaidPolicyByPaymentNumber($policyNumber)
    {
        return Policy::where('number', $policyNumber)
            ->where('paid', 0)
            ->first();
    }

    public function update($id, $data)
    {
        return Policy::where('id', $id)->update($data);
    }
}
