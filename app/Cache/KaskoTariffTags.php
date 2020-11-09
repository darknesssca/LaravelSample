<?php


namespace App\Cache;


trait KaskoTariffTags
{
    protected static function getKaskoTariffTag(): string
    {
        return "KaskoTariff";
    }

    protected static function getKaskoTariffListKey(): string
    {
        return "KaskoTariff|List";
    }
}
