<?php


namespace App\Repositories;


use App\Contracts\Repositories\RequestProcessRepositoryContract;
use App\Models\RequestProcess;

class RequestProcessRepository implements RequestProcessRepositoryContract
{
    public function getPool($state, $limit)
    {
        return RequestProcess::where('state', $state)
            ->limit($limit)
            ->get();
    }

    public function update($token, $companyCode, $data)
    {
        return RequestProcess::where('token', $token)
            ->where('company', $companyCode)
            ->update($data);
    }

    public function find($token, $companyCode)
    {
        return RequestProcess::where('token', $token)
            ->where('company', $companyCode)
            ->first();
    }

    public function create($data)
    {
        return RequestProcess::create($data);
    }

    public function delete($token, $companyCode)
    {
        return RequestProcess::where('token', $token)
            ->where('company', $companyCode)
            ->delete();
    }
}
