<?php


namespace App\Repositories;


use App\Cache\Car\CarModelCacheTags;
use App\Contracts\Repositories\CarModelRepositoryContract;
use App\Models\CarModel;
use Benfin\Cache\CacheKeysTrait;
use Illuminate\Support\Facades\Cache;

class CarModelRepository implements CarModelRepositoryContract
{
    use CarModelCacheTags, CacheKeysTrait;

    private $CACHE_DAY_TTL = 24 * 60 * 60;


    public function getModelListByMarkId($mark_id)
    {
        $cacheTag = self::getCarModelTag();
        $cacheKey = self::getCacheKey("ListById", $mark_id);

        return Cache::tags($cacheTag)->remember($cacheKey, $this->CACHE_DAY_TTL, function () use ($mark_id) {
            return CarModel::select(["id", "code", "name", "category_id", "mark_id"])
                ->where("mark_id", $mark_id)->get();
        });
    }

    public function getModelList()
    {
        $cacheTag = self::getCarModelTag();
        $cacheKey = self::getCarModelListKey();

        return Cache::tags($cacheTag)->remember($cacheKey, $this->CACHE_DAY_TTL, function () {
            return CarModel::select(["id", "code", "name", "category_id", "mark_id"])->get();
        });
    }

    public function getCompanyModel($mark_id, $id, $companyId)
    {
        $cacheTag = self::getCarModelTag();
        $cacheKey = self::getCacheKey("Company", $mark_id, $id, $companyId);

        return Cache::tags($cacheTag)->remember($cacheKey, $this->CACHE_DAY_TTL,
            function () use ($mark_id, $id, $companyId) {
                return CarModel::with(['codes' => function ($query) use ($companyId) {
                    $query->where('insurance_company_id', $companyId);
                }])
                    ->with(['category'])
                    ->where('id', $id)
                    ->where('mark_id', $mark_id)
                    ->first();
            }
        );

    }

    public function getCompanyModelByName($mark_id, $category_id, $name, $companyId)
    {
        $cacheTag = self::getCarModelTag();
        $cacheKey = self::getCacheKey("Name", $mark_id, $name, $companyId);

        return Cache::tags($cacheTag)->remember($cacheKey, $this->CACHE_DAY_TTL,
            function () use ($mark_id, $name, $companyId) {
                return CarModel::with([
                    'codes' => function ($query) use ($companyId) {
                        $query->where('insurance_company_id', $companyId);
                    },
                ])
                    ->with(['category'])
                    ->where('mark_id', $mark_id)
                    ->where('name', 'ilike', $name)
                    ->first();
            }
        );
    }

    public function getCompanyOtherModel($mark_id, $categoryId, $companyId)
    {
        $cacheTag = self::getCarModelTag();
        $cacheKey = self::getCacheKey("Other", $mark_id, $categoryId, $companyId);

        return Cache::tags($cacheTag)->remember($cacheKey, $this->CACHE_DAY_TTL,
            function () use ($mark_id, $categoryId, $companyId) {
                return CarModel::with([
                    'codes' => function ($query) use ($companyId) {
                        $query->where('insurance_company_id', $companyId);
                    },
                ])
                    ->with(['category'])
                    ->where('mark_id', $mark_id)
                    ->where('code', 'like', 'drug%')
                    //->where('code', 'drugaya-model-legkovoy')
                    ->where('category_id', $categoryId)
                    ->first();
            }
        );
    }
}
