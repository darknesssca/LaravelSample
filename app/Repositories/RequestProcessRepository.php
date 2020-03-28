<?php


namespace App\Repositories;


use App\Contracts\Repositories\RequestProcessRepositoryContract;
use App\Models\RequestProcess;

abstract class RequestProcessRepository extends AbstractDataRepository implements RequestProcessRepositoryContract
{
    public function __construct(RequestProcess $model)
    {
        parent::__construct($model);
    }
}
