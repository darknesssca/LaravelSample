<?php


namespace App\Repositories;


use App\Contracts\Repositories\PolicyRepositoryContract;
use App\Models\Policy;

class PolicyRepository extends AbstractDataRepository implements PolicyRepositoryContract
{
    public function __construct(Policy $model)
    {
        parent::__construct($model);
    }

    public function getNotPaidPolicyByPaymentNumber($policyNumber)
    {
        return $this->model
            ->where('number', $policyNumber)
            ->where('paid', 0)
            ->first();
    }

}
