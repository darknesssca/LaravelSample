<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InsuranceModel extends Model
{
    protected $fillable = [
        'model_id',
        'insurance_company_id',
        'reference_model_code',
    ];
}
