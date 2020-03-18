<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GenderInsurance extends Model
{
    protected $fillable = [
        'gender_id',
        'insurance_company_id',
        'reference_gender_code',
    ];
    protected $table = 'gender_insurance';

}
