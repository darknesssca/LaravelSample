<?php

namespace App\Models;

use App\Observers\CountryObserver;
use Illuminate\Database\Eloquent\Model;

class Country extends Model
{
    use CountryObserver;

    protected $fillable = [
        'code',
        'name',
        'short_name',
        'alpha2',
        'alpha3',
    ];
    protected $table = 'countries';

}
