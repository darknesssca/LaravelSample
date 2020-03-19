<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Gender extends Model
{
    protected $fillable = [
        'code',
        'name',
    ];
    protected $table = 'genders';

    public function insuranceCodes()
    {
        return $this->belongsTo('App\Models\GenderInsurance');
    }

}
