<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Driver extends Model
{
    protected $fillable = [
        'first_name',
        'last_name',
        'patronymic',
        'birth_date',
        'license_series',
        'license_number',
        'license_is_russian',
        'license_date',
        'exp_start_date',
        'address',
        'address_json',
        'gender_id',
        'citizenship_id'
    ];

    protected $table = 'drivers';

    protected $casts = [
        'address_json' => 'array'
    ];

    public function policy()
    {
        return $this->belongsToMany('App\Models\Policy');
    }

    public function gender()
    {
        return $this->belongsTo('App\Models\Gender');
    }

    public function citizenship()
    {
        return $this->belongsTo('App\Models\Country');
    }
}
