<?php


namespace App\Repositories;


use App\Contracts\Repositories\UsageTargetRepositoryContract;
use App\Models\UsageTarget;

class UsageTargetRepository implements UsageTargetRepositoryContract
{
    public function getUsageTargetList()
    {
        return UsageTarget::select(["id", "code", "name"])->get();
    }

    public function getCompanyUsageTarget($id, $companyId)
    {
        return UsageTarget::with([
            'codes' => function ($query) use ($companyId) {
                $query->where('insurance_company_id', $companyId);
            }
        ])
            ->where('id', $id)->first();
    }
}
