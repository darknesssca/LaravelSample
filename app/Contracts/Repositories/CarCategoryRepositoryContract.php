<?php


namespace App\Contracts\Repositories;


interface CarCategoryRepositoryContract
{
    public function getCategoryList();
    public function getCategoryById($id);
    public function getCompanyCategory($categoryCode, $companyCode);
}
