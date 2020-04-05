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

    public function codes()
    {
        return $this->hasMany('App\Models\GenderInsurance','gender_id', 'id');
    }

}
