<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CarModelInsurance extends Model
{
    protected $fillable = [
        'model_id',
        'insurance_company_id',
        'reference_model_code',
    ];
    protected $table = 'insurance_model';

}
