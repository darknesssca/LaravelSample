<?php


namespace App\Contracts\Repositories\Services;


interface CarModelServiceContract
{
    public function getModelList();
    public function getModelListByMarkId($mark_id);
}
