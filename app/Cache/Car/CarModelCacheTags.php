<?php


namespace App\Cache\Car;


trait CarModelCacheTags
{
    protected static function getCarModelTag(): string
    {
        return "CarModel";
    }

    protected static function getCarModelListKey(): string
    {
        return "CarModel|List";
    }
}
