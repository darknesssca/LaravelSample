<?php


namespace App\Cache;


trait UsageTargetCacheTag
{
    protected static function getUsageTargetTag(): string
    {
        return "UsageTarget";
    }

    protected static function getUsageTargetListKey(): string
    {
        return "UsageTarget|List";
    }
}
