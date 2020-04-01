<?php


namespace App\Contracts\Repositories;


interface CountryRepositoryContract
{
    public function getCountryList();
    public function getCountryById($country_id);
}
