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

    public function update($token, $data)
    {
        return RequestProcess::where('token', $token)->update($data);
    }

    public function find($token)
    {
        return RequestProcess::find($token);
    }

    public function create($data)
    {
        return RequestProcess::create($data);
    }
}
