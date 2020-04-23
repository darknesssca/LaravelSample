<?php


namespace App\Repositories;


use App\Cache\CountryCacheTags;
use App\Contracts\Repositories\CountryRepositoryContract;
use App\Models\Country;
use Benfin\Cache\CacheKeysTrait;
use Illuminate\Support\Facades\Cache;

class CountryRepository implements CountryRepositoryContract
{
    use CountryCacheTags, CacheKeysTrait;

    private $CACHE_DAY_TTL = 24 * 60 * 60;

    public function getCountryList()
    {
        $cacheTag = self::getCountryTag();
        $cacheKey = self::getCountryListKey();

        return Cache::tags($cacheTag)->remember($cacheKey, $this->CACHE_DAY_TTL, function () {
            return Country::select(["id", "code", "name", "short_name", "alpha2", "alpha3"])->get();
        });
    }

    public function getCountryById($country_id)
    {
        $cacheTag = self::getCountryTag();
        $cacheKey = self::getCacheKey($country_id);

        return Cache::tags($cacheTag)->remember($cacheKey, $this->CACHE_DAY_TTL, function () use ($country_id) {
            return Country::select(["id", "code", "name", "short_name", "alpha2", "alpha3"])->where("id", $country_id)
                ->first();
        });
    }
}
