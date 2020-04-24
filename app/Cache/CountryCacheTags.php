<?php


namespace App\Cache;


trait CountryCacheTags
{
    protected static function getCountryTag(): string
    {
        return "Country";
    }

    protected static function getCountryListKey(): string
    {
        return "Country|List";
    }
}
