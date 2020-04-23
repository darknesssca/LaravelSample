<?php


namespace App\Repositories;


use App\Cache\InsuranceCompanyCacheTag;
use App\Contracts\Repositories\InsuranceCompanyRepositoryContract;
use App\Models\InsuranceCompany;
use Benfin\Cache\CacheKeysTrait;
use Illuminate\Support\Facades\Cache;

class InsuranceCompanyRepository implements InsuranceCompanyRepositoryContract
{
    use InsuranceCompanyCacheTag, CacheKeysTrait;

    private $CACHE_DAY_TTL = 24 * 60 * 60;

    public function getCompany($code)
    {
        $cacheTag = self::getInsuranceCompanyTag();
        $cacheKey = self::getCacheKey($code);

        return Cache::tags($cacheTag)->remember($cacheKey, $this->CACHE_DAY_TTL, function () use ($code) {
            return InsuranceCompany::where([
                'code' => $code,
                'active' => true,
            ])->first();
        });
    }

    public function getCompanyById($id)
    {
        return InsuranceCompany::where([
            'id' => $id,
            'active' => true,
        ])
            ->first();
    }

    public function getInsuranceCompanyList()
    {
        $cacheTag = self::getInsuranceCompanyTag();
        $cacheKey = self::getInsuranceCompanyListKey();

        return Cache::tags($cacheTag)->remember($cacheKey, $this->CACHE_DAY_TTL, function () {
            return InsuranceCompany::select(["id", "code", "name"])->where("active", true)->get();
        });
    }

    public function getList()
    {
        // TODO: Implement getList() method.
    }

    public function getById(int $id)
    {
        return InsuranceCompany::query()->find($id)->first();
    }
}
