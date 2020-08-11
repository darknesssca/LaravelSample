<?php


namespace App\Contracts\Repositories\Services;


interface CarCategoryServiceContract
{
    public function getCategoryList();
    public function getCompanyCategory($categoryId, $isUsedWithTrailer, $companyCode);
    public function getCategoryById($categoryId);
}
