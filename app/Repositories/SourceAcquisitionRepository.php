<?php


namespace App\Repositories;


use App\Cache\SourceAcquisitionCacheTag;
use App\Contracts\Repositories\SourceAcquisitionRepositoryContract;
use App\Models\SourceAcquisition;
use Benfin\Cache\CacheKeysTrait;
use Illuminate\Support\Facades\Cache;

class SourceAcquisitionRepository implements SourceAcquisitionRepositoryContract
{
    use SourceAcquisitionCacheTag, CacheKeysTrait;

    private $CACHE_DAY_TTL = 24 * 60 * 60;

    public function getSourceAcquisitionsList()
    {
        $cacheTag = self::getSourceAcquisitionTag();
        $cacheKey = self::getSourceAcquisitionListKey();

        return Cache::tags($cacheTag)->remember($cacheKey, $this->CACHE_DAY_TTL, function () {
            return SourceAcquisition::select(["id", "code", "name"])->get();
        });
    }

    public function getCompanySourceAcquisitions($id, $companyId)
    {

        $cacheTag = self::getSourceAcquisitionTag();
        $cacheKey = self::getCacheKey($id, $companyId);

        return Cache::tags($cacheTag)->remember($cacheKey, $this->CACHE_DAY_TTL, function () use ($id, $companyId) {
            return SourceAcquisition::with([
                'codes' => function ($query) use ($companyId) {
                    $query->where('insurance_company_id', $companyId);
                }
            ])->where('id', $id)->first();
        });
    }
}
