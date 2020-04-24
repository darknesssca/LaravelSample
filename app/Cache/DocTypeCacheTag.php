<?php


namespace App\Cache;


trait DocTypeCacheTag
{
    protected static function getDocTypeTag(): string
    {
        return "DocType";
    }

    protected static function getDocTypeListKey(): string
    {
        return "DocType|List";
    }

}
