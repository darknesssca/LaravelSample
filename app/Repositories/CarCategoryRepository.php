<?php


namespace App\Repositories;


use App\Contracts\Repositories\CarCategoryRepositoryContract;
use App\Models\CarCategory;

class CarCategoryRepository implements CarCategoryRepositoryContract
{
    public function getCategoryList()
    {
        return CarCategory::select(["id", "code", "name"])->get();
    }

    public function getCategoryById($id)
    {
        return CarCategory::where('id', $id)->first();
    }

    public function getCompanyCategory($categoryCode, $companyCode)
    {
        $codes = $this->getCompanyCategoryRelations();
        return isset($codes[$companyCode][$categoryCode]) ? $codes[$companyCode][$categoryCode] : null;
    }

    protected function getCompanyCategoryRelations()
    {
        return [
            'soglasie' => [
                'a' => 1,
                'b' => [
                    'default' => 2,
                    'trailer' => 8,
                ],
                'c' => [
                    'default' => 3,
                    'trailer' => 9,
                ],
                'd' => 4,
                'traktor' => [
                    'default' => 7,
                    'trailer' => 24,
                ],
                //'e' => 1,
                'tb' => 5,
                'tm' => 6,
                'vezdekhod' => 7,
                'f' => [
                    'default' => 7,
                    'trailer' => 10,
                ],
                'pogruzchik' => 7,
                'avtokran' => 7,
                'kommunalnaya' => 7,
                'kran' => 7,
                //'treyler' => 1,
            ],
        ];
    }
}
