<?php


namespace App\Cache\Car;


trait CarMarkCacheTags
{
    protected static function getCarMarkTag(): string
    {
        return "CarMark";
    }

    protected static function getCarMarcListKey(): string
    {
        return "CarMark|List";
    }

}
