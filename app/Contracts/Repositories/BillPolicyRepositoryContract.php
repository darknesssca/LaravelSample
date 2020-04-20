<?php


namespace App\Contracts\Repositories;


interface BillPolicyRepositoryContract
{
    public function create($policyId, $billId);
    public function delete($policyId);
}
