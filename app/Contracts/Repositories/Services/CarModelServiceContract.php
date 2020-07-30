<?php


namespace App\Contracts\Repositories\Services;


interface CarModelServiceContract
{
    public function getModelList();
    public function getModelListByMarkId($mark_id, $categoryId = null);
    public function getCompanyModel($mark_id, $id, $companyId);
    public function getCompanyModelByName($mark_id, $categoryId, $name, $companyId, $needOther = true);
}
