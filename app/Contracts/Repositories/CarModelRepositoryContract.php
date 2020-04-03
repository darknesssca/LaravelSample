<?php


namespace App\Contracts\Repositories;


interface CarModelRepositoryContract
{
    public function getModelListByMarkId($mark_id);
    public function getModelList();
    public function getCompanyModel($mark_id, $id, $companyId);
    public function getCompanyModelByName($mark_id, $name, $companyId);
    public function getCompanyOtherModel($mark_id, $categoryId, $companyId);
}
