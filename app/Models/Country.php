<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Country extends Model
{
    protected $fillable = [
        'code',
        'name',
        'short_name',
        'alpha2',
        'alpha3',
    ];
    protected $table = 'countries';

}
