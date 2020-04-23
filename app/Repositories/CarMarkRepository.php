<?php


namespace App\Repositories;


use App\Cache\Car\CarMarkCacheTags;
use App\Contracts\Repositories\CarMarkRepositoryContract;
use App\Models\CarMark;
use Benfin\Cache\CacheKeysTrait;
use Illuminate\Support\Facades\Cache;

class CarMarkRepository implements CarMarkRepositoryContract
{
    use CarMarkCacheTags, CacheKeysTrait;

    private $CACHE_DAY_TTL = 24 * 60 * 60;

    public function getMarkList()
    {
        $cacheTag = self::getCarMarkTag();
        $cacheKey = self::getCarMarcListKey();

        return Cache::tags($cacheTag)->remember($cacheKey, $this->CACHE_DAY_TTL, function () {
            return CarMark::select(["id", "code", "name"])->get();
        });
    }

    public function getCompanyMark($id, $companyId)
    {
        $cacheTag = self::getCarMarkTag();
        $cacheKey = self::getCacheKey($id, $companyId);

        return Cache::tags($cacheTag)->remember($cacheKey, $this->CACHE_DAY_TTL, function () use ($id, $companyId) {
            return CarMark::with([
                'codes' => function ($query) use ($companyId) {
                    $query->where('insurance_company_id', $companyId);
                }
            ])->where('id', $id)->first();
        });

    }

    public function getCarMarkById($id)
    {
        $cacheTag = self::getCarMarkTag();
        $cacheKey = self::getCacheKey($id);

        return Cache::tags($cacheTag)->remember($cacheKey, $this->CACHE_DAY_TTL, function () use ($id) {
            return CarMark::where('id', $id)->first();
        });
    }
}
