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
}
