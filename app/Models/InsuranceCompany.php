<?php

namespace App\Models;

use App\Observers\InsuranceCompanyObserver;
use Illuminate\Database\Eloquent\Model;

class InsuranceCompany extends Model
{
    use InsuranceCompanyObserver;

    protected $fillable = [
        'active',
        'logo_id',
        'code',
        'name',
    ];

    protected $table = 'insurance_companies';

    public function getRouteKey()
    {
        return 'code';
    }

    public function logo()
    {
        return $this->belongsTo('App\Models\File', 'logo_id', 'id');
    }
}
