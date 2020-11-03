<?php


namespace App\Repositories;


use App\Contracts\Repositories\IntermediateDataRepositoryContract;
use App\Models\IntermediateData;

class IntermediateDataRepository implements IntermediateDataRepositoryContract
{
    public function update($token, $data)
    {
        return IntermediateData::where('token', $token)->update($data);
    }

    public function find($token)
    {
        return IntermediateData::find($token);
    }

    public function create($data)
    {
        return IntermediateData::create($data);
    }

    public function delete($token)
    {
        return IntermediateData::where('token', $token)->delete();
    }

    public function getByData(string $data)
    {
        return IntermediateData::where('data', 'ilike', '%' . $data . '%')->first();
    }
}
