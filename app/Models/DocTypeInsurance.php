<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DocTypeInsurance extends Model
{
    protected $fillable = [
        'doctype_id',
        'insurance_company_id',
        'reference_doctype_code',
    ];
    protected $table = 'doctype_insurance';

}
