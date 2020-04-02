<?php


namespace App\Services\Repositories;


use App\Contracts\Repositories\CountryRepositoryContract;
use App\Contracts\Repositories\Services\CountryServiceContract;
use App\Exceptions\GuidesNotFoundException;
use Benfin\Cache\CacheTrait;
use Illuminate\Support\Facades\Cache;

class CountryService implements CountryServiceContract
{
    use CacheTrait;

    protected $countryRepository;

    public function __construct(
        CountryRepositoryContract $countryRepository
    )
    {
        $this->countryRepository = $countryRepository;
    }

    public function getCountryList()
    {
        $tag = $this->getGuidesCountriesTag();
        $key = $this->getCacheKey($tag, 'all');
        $data = Cache::tags($tag)->remember($key, config('cache.guidesCacheTtl'), function () {
            return $this->countryRepository->getCountryList();
        });
        if (!$data || !$data->count()) {
            throw new GuidesNotFoundException('Не найдены данные в справочнике');
        }
        return $data->jsonSerialize();
    }

    public function getCountryById($country_id)
    {
        $tag = $this->getGuidesCountriesTag();
        $key = $this->getCacheKey($tag, $country_id);
        $data = Cache::tags($tag)->remember($key, config('cache.guidesCacheTtl'), function () use ($country_id) {
            return $this->countryRepository->getCountryById($country_id);
        });
        if (!$data || !$data->count()) {
            throw new GuidesNotFoundException('Не найдены данные в справочнике');
        }
        return $data->jsonSerialize();
    }
}
