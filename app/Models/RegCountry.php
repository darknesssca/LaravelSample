<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RegCountry extends Model
{
    protected $fillable = [
        'code',
        'name',
    ];
    protected $table = 'reg_countries';

    public function insuranceCodes()
    {
        return $this->belongsToMany('App\Models\RegCountryInsurance');
    }
}
