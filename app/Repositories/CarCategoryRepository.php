<?php


namespace App\Repositories;


use App\Cache\Car\CarCategoryCacheTags;
use App\Contracts\Repositories\CarCategoryRepositoryContract;
use App\Models\CarCategory;
use Benfin\Cache\CacheKeysTrait;
use Illuminate\Support\Facades\Cache;

class CarCategoryRepository implements CarCategoryRepositoryContract
{
    use CarCategoryCacheTags, CacheKeysTrait;

    private $CACHE_DAY_TTL  = 24 * 60 * 60;

    public function getCategoryList()
    {
        $cacheTag = self::getCarCategoryTag();
        $cacheKey = self::getCarCategoryListKey();

        return Cache::tags($cacheTag)->remember($cacheKey, $this->CACHE_DAY_TTL, function () {
            return CarCategory::select(["id", "code", "name"])->get();
        });
    }

    public function getCategoryById($id)
    {
        $cacheTag = self::getCarCategoryTag();
        $cacheKey = self::generateCacheKey($id);

        return Cache::tags($cacheTag)->remember($cacheKey, $this->CACHE_DAY_TTL, function () use ($id) {
            return CarCategory::where('id', $id)->first();
        });
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
