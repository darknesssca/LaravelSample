<?php


namespace App\Repositories;


use App\Contracts\Repositories\IntermediateDataRepositoryContract;
use App\Models\IntermediateData;

abstract class IntermediateDataRepository extends AbstractDataRepository implements IntermediateDataRepositoryContract
{
    public function __construct(IntermediateData $model)
    {
        parent::__construct($model);
    }

    public function getToken($token, $force = false)
    {
        if ($force) {
            return $this->model->find($token);
        }
        return $this->model->load($token);
    }

    public function update($token, $data)
    {
        return $this->model->where('token', $token)->update($data);
    }

}
