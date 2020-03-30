<?php


namespace App\Repositories;


use App\Contracts\Repositories\RequestProcessRepositoryContract;
use App\Models\RequestProcess;

class RequestProcessRepository extends AbstractDataRepository implements RequestProcessRepositoryContract
{
    public function __construct(RequestProcess $model)
    {
        parent::__construct($model);
    }

    public function getPool($state, $count)
    {
        return $this->model
            ->where('state', $state)
            ->limit($count)
            ->get();
    }

    public function updateCheckCount($token)
    {
        $object = $this->model->where('token', $token)->first();
        $checkCount = ++$object->checkCount;
        if ($checkCount >= config('api_sk.maxCheckCount')) {
            $object->delete();
            return false;
        }
        $object->update(['checkCount' => $checkCount]);
        return true;

    }
}
