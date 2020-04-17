<?php


namespace App\Cache;


trait GenderCacheTag
{
    protected static function getGenderTag(): string
    {
        return "Gender";
    }

    protected static function getGenderListKey(): string
    {
        return "Gender|List";
    }
}
