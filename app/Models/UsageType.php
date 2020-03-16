<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UsageType extends Model
{
    protected $fillable = [
        'code',
        'name',
    ];
    protected $table = 'usage_types';

    public function insuranceCodes()
    {
        return $this->belongsToMany('App\Models\UsageTypeInsurance');
    }

}
