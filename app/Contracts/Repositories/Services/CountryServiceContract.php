<?php


namespace App\Contracts\Repositories\Services;


interface CountryServiceContract
{
    public function getCountryList();
    public function getCountryById($country_id);
}
