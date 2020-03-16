<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UsageTarget extends Model
{
    protected $fillable = [
        'code',
        'name',
    ];
    protected $table = 'usage_targets';

    public function insuranceCodes()
    {
        return $this->belongsToMany('App\Models\UsageTargetInsurance');
    }

}
