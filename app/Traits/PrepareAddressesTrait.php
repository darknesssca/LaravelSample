<?php


namespace App\Traits;

use Carbon\Carbon;

trait PrepareAddressesTrait
{
    protected function cutCityKladr(&$kladrCode)
    {
        if (strlen($kladrCode) > 11) {
            $kladrCode = substr($kladrCode, 0, -2);
        }
    }

    protected function cutStreetKladr(&$kladrCode)
    {
        if (strlen($kladrCode) > 15) {
            $kladrCode = substr($kladrCode, 0, -2);
        }
    }
}
