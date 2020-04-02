<?php


namespace App\Contracts\Repositories\Services;


interface CarModelServiceContract
{
    public function getModelList();
    public function getModelListByMarkId($mark_id);
    public function getCompanyModel($mark_id, $id, $companyId);
    public function getCompanyModelByName($mark_id, $name, $companyId);
}
