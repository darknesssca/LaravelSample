<?php


namespace App\Contracts\Repositories;


interface UsageTargetRepositoryContract
{
    public function getUsageTargetList();
    public function getCompanyUsageTarget($id, $companyId);
}
