<?php


namespace App\Contracts\Repositories\Services;


interface UsageTargetServiceContract
{
    public function getUsageTargetList();
    public function getCompanyUsageTarget($id, $companyId);
}
