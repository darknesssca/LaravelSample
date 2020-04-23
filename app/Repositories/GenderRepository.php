<?php


namespace App\Repositories;


use App\Cache\GenderCacheTag;
use App\Contracts\Repositories\GenderRepositoryContract;
use App\Models\Gender;
use Benfin\Cache\CacheKeysTrait;
use Illuminate\Support\Facades\Cache;

class GenderRepository implements GenderRepositoryContract
{
    use GenderCacheTag, CacheKeysTrait;

    private $CACHE_DAY_TTL = 24 * 60 * 60;

    public function getGendersList()
    {
        $cacheTag = self::getGenderTag();
        $cacheKey = self::getGenderListKey();

        return Cache::tags($cacheTag)->remember($cacheKey, $this->CACHE_DAY_TTL, function () {
            return Gender::select(["id", "code", "name"])->get();
        });
    }

    public function getCompanyGender($id, $companyId)
    {
        $cacheTag = self::getGenderTag();
        $cacheKey = self::getCacheKey($id, $companyId);

        return Cache::tags($cacheTag)->remember($cacheKey, $this->CACHE_DAY_TTL, function () use ($id, $companyId) {
            return Gender::with([
                'codes' => function ($query) use ($companyId) {
                    $query->where('insurance_company_id', $companyId);
                }
            ])
                ->where('id', $id)->first();
        });

    }
}
