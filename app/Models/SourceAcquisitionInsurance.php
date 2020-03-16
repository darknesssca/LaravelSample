<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SourceAcquisitionInsurance extends Model
{
    protected $fillable = [
        'acquisition_id',
        'insurance_company_id',
        'reference_acquisition_code',
    ];
    protected $table = 'acquisition_insurance';

}
