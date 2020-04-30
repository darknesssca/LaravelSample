<?php


namespace App\Repositories;


use App\Cache\InsuranceCompanyCacheTag;
use App\Contracts\Repositories\InsuranceCompanyRepositoryContract;
use App\Exceptions\ObjectNotFoundException;
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
        ])->first();
    }

    public function getInsuranceCompanyList($checkActive = true)
    {
        $cacheTag = self::getInsuranceCompanyTag();
        $cacheKey = self::getInsuranceCompanyListKey() . strval($checkActive);
        return Cache::tags($cacheTag)->remember($cacheKey, $this->CACHE_DAY_TTL, function () use ($checkActive) {
            if ($checkActive)
                return InsuranceCompany::query()->with('logo')->where("active", true)->get()->sortBy('code');
            else
                return InsuranceCompany::query()->with('logo')->get()->sortBy('code');
        });
    }

    public function getById(int $id)
    {
        return InsuranceCompany::query()->find($id)->first();
    }

    /**обновляет активность у всех заданных компаний
     * @param $params
     * @return array
     * @throws ObjectNotFoundException
     */
    public function updateActivity($params)
    {
        $result = [];
        foreach ($params as $code => $value) {
            $company = InsuranceCompany::where('code', $code)->first();
            if ($company == null)
                throw new ObjectNotFoundException("Не найдена компания с кодом $code");
            $company->active = boolval($value);
            $company->save();
            $result[] = $company;
        }
        return $result;
    }
}
