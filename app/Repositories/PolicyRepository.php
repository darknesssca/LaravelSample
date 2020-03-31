<?php


namespace App\Repositories;


use App\Contracts\Repositories\PolicyRepositoryContract;
use App\Models\Policy;
use Carbon\Carbon;

class PolicyRepository implements PolicyRepositoryContract
{
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

    public function deleteBill($id)
    {

    }
}
