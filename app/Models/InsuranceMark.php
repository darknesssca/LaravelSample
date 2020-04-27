<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InsuranceMark extends Model
{
    protected $fillable = [
        'mark_id',
        'insurance_company_id',
        'reference_mark_code',
    ];

    protected $primaryKey = array('mark_id','insurance_company_id');
    public $incrementing = false;
}
