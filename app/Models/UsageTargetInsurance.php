<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UsageTargetInsurance extends Model
{
    protected $fillable = [
        'target_id',
        'insurance_company_id',
        'reference_usage_target_code',
    ];
    protected $table = 'usage_target_insurance';

}
