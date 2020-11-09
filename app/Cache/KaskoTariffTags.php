<?php


namespace App\Cache;


trait KaskoTariffTags
{
    protected static function getKaskoTariffListTag(): string
    {
        return "KaskoTariff|List";
    }
}
