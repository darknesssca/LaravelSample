<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UsageTypeInsurance extends Model
{
    protected $fillable = [
        'type_id',
        'insurance_company_id',
        'reference_usage_type_code',
    ];
    protected $table = 'usage_type_insurance';

}
