<?php


namespace App\Models;


use App\Observers\KaskoTariffObserver;
use Illuminate\Database\Eloquent\Model;

class KaskoTariff extends Model
{
    use KaskoTariffObserver;

    protected $table = 'kasko_tariffs';
}
