<?php


namespace App\Cache;


trait SourceAcquisitionCacheTag
{
    protected static function getSourceAcquisitionTag(): string
    {
        return "SourceAcquisition";
    }

    protected static function getSourceAcquisitionListKey(): string
    {
        return "SourceAcquisition|List";
    }
}
