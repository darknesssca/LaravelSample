<?php

namespace App\Services;

use App\Contracts\Services\PolicyServiceContract;
use App\Models\Policy;

class PolicyService implements PolicyServiceContract
{

    private const STATUS_ISSUED = 2;

    public function getList()
    {
        return Policy::all();
    }

    public function getById($id)
    {
        return Policy::findOrFail($id);
    }

    public function create()
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
