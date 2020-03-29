<?php


namespace App\Traits;

use Carbon\Carbon;

trait DateFormat
{
    protected function dateTimeZoneFromDate($date)
    {
        $date = Carbon::createFromFormat('Y-m-d', $date);
        return $date->format('Y-m-d\TH:i:sP');
    }

    protected function formatToRuDate($date)
    {
        $date = Carbon::createFromFormat('Y-m-d', $date);
        return $date->format('d-m-Y');
    }

    protected function dateTimeFromDate($date)
    {
        $date = Carbon::createFromFormat('Y-m-d', $date);
        return $date->format('Y-m-d\TH:i:s');
    }

    protected function dateFromYear($year)
    {
        $date = Carbon::createFromFormat('Y', $year);
        return $date->format('Y-m-d');
    }

    protected function getYearsOld($birthDate)
    {
        $date = Carbon::createFromFormat('Y-m-d', $birthDate);
        $now = date('Y');
        return (int)$now - (int)$date->format('Y');
    }

    protected function getYearFromDate($date)
    {
        $date = Carbon::createFromFormat('Y-m-d', $date);
        return $date->format('Y');
    }

    protected function getMonthFromDate($date)
    {
        $date = Carbon::createFromFormat('Y-m-d', $date);
        return $date->format('m');
    }
}
