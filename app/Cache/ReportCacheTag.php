<?php


namespace App\Cache;


trait ReportCacheTag
{
    protected static function getReportCacheTag(): string
    {
        return "Report";
    }

    protected static function getReportCacheTagByAttribute($attribute): string
    {
        return self::getReportCacheTag() . "|$attribute";
    }
}
