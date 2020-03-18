<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CarMarkInsurance extends Model
{
    protected $fillable = [
        'mark_id',
        'insurance_company_id',
        'reference_mark_code',
    ];
    protected $table = 'insurance_mark';

}
