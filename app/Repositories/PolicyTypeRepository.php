<?php



namespace App\Repositories;


use App\Contracts\Repositories\PolicyTypeRepositoryContract;
use App\Models\PolicyType;

class PolicyTypeRepository implements PolicyTypeRepositoryContract
{
    public function getByCode($code)
    {
        return PolicyType::where('code', $code)->first();
    }
}
