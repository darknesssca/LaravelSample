<?php


namespace App\Repositories;


use App\Cache\UsageTargetCacheTag;
use App\Contracts\Repositories\UsageTargetRepositoryContract;
use App\Models\UsageTarget;
use Benfin\Cache\CacheKeysTrait;
use Illuminate\Support\Facades\Cache;

class UsageTargetRepository implements UsageTargetRepositoryContract
{
    use UsageTargetCacheTag, CacheKeysTrait;

    private $CACHE_DAY_TTL = 24 * 60 * 60;

    public function getUsageTargetList()
    {
        $cacheTag = self::getUsageTargetTag();
        $cacheKey = self::getUsageTargetListKey();

        return Cache::tags($cacheTag)->remember($cacheKey, $this->CACHE_DAY_TTL, function () {
            return UsageTarget::select(["id", "code", "name"])->get();
        });
    }

    public function getCompanyUsageTarget($id, $companyId)
    {

        $cacheTag = self::getUsageTargetTag();
        $cacheKey = self::getCacheKey($id, $companyId);

        return Cache::tags($cacheTag)->remember($cacheKey, $this->CACHE_DAY_TTL, function () use ($id, $companyId) {
            return UsageTarget::with([
                'codes' => function ($query) use ($companyId) {
                    $query->where('insurance_company_id', $companyId);
                }
            ])->where('id', $id)->first();
        });
    }
}
