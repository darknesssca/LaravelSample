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
}
