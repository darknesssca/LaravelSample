<?php


namespace App\Repositories;


use App\Contracts\Repositories\CountryRepositoryContract;
use App\Models\Country;

class CountryRepository implements CountryRepositoryContract
{
    public function getCountryList()
    {
        return Country::select(["id", "code", "name", "short_name", "alpha2", "alpha3"])->get();
    }

    public function getCountryById($country_id)
    {
        return Country::select(["id", "code", "name", "short_name", "alpha2", "alpha3"])->where("id", $country_id)->get();
    }
}
