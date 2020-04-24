<?php


namespace App\Cache;


trait OptionCacheTag
{
    protected static function getOptionCacheTag(): string
    {
        return "Option";
    }

    protected static function getOptionListKey(): string
    {
        return "Option:List";
    }
}
