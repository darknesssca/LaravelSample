<?php


namespace App\Observers;


use Benfin\Cache\Observers\AbstractObserver;

class Observer extends AbstractObserver
{
    protected $observe = [
        'CarMark',
        'CarModel',
        'CarCategory',
        'Country',
        'Gender',
        'DocType',
        'UsageTarget',
        'InsuranceCompany',
        'SourceAcquisition',
    ];
}
