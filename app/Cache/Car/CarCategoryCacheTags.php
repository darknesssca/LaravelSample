<?php


namespace App\Cache\Car;


trait CarCategoryCacheTags
{
    protected static function getCarCategoryTag(): string
    {
        return "CarCategory";
    }

    protected static function getCarCategoryListKey(): string
    {
        return "category|list";
    }
}
