<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

class InsuranceCompany extends Model
{

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
        return $this->belongsTo('App\Models\Files');
    }

    public static function scopeGetCompany($query, $code)
    {
        return $query->where([
            'code' => $code,
            'active' => true,
        ])->first();
    }
}
