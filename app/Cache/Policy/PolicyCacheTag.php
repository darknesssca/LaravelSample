<?php


namespace App\Cache\Policy;


use Benfin\Api\GlobalStorage;

trait PolicyCacheTag
{
    protected static function getPolicyCacheTag(): string
    {
        return "Policy";
    }

    protected static function getPolicyListCacheTagByUser(): string
    {
        $userId = GlobalStorage::getUserId() ?? "";
        return self::getPolicyCacheTag() . "|List|$userId";
    }

    protected static function getPolicyListCacheTagByAttribute($attribute): string
    {
        return self::getPolicyCacheTag() . "$attribute";
    }
}
