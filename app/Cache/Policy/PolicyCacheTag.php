<?php


namespace App\Cache\Policy;


trait PolicyCacheTag
{
    protected static function getPolicyCacheTag(): string
    {
        return "Policy";
    }
}
