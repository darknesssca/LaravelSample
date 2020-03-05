<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

class InsuranceCompany extends Model
{
    protected $table = 'insurance_companies';

    public function getRouteKey()
    {
        return 'code';
    }

    public static function scopeGetCompany($query, $code)
    {
        return $query->where([
            'code' => $code,
            'active' => true,
        ])->first();
    }
}
