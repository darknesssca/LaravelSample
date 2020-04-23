<?php


namespace App\Services\Repositories;


use App\Contracts\Repositories\CountryRepositoryContract;
use App\Contracts\Repositories\Services\CountryServiceContract;
use App\Exceptions\GuidesNotFoundException;

class CountryService implements CountryServiceContract
{
    protected $countryRepository;

    public function __construct(
        CountryRepositoryContract $countryRepository
    )
    {
        $this->countryRepository = $countryRepository;
    }

    public function getCountryList()
    {
        $data = $this->countryRepository->getCountryList();

        if (!$data || !$data->count()) {
            throw new GuidesNotFoundException('Не найдены данные в справочнике');
        }

        return $data->jsonSerialize();
    }

    public function getCountryById($country_id)
    {
        $data = $this->countryRepository->getCountryById($country_id);

        if (!$data) {
            throw new GuidesNotFoundException('Не найдены данные в справочнике');
        }

        return $data->toArray();
    }
}
