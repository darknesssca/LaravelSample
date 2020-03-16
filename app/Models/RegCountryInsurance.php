<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RegCountryInsurance extends Model
{
    protected $fillable = [
        'country_id',
        'insurance_company_id',
        'reference_country_code',
    ];
    protected $table = 'insurance_country';

}
