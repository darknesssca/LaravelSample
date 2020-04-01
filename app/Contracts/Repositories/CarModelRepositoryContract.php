<?php


namespace App\Contracts\Repositories;


interface CarModelRepositoryContract
{
    public function getModelListByMarkId($mark_id);
    public function getModelList();
}
