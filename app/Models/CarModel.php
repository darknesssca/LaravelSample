<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CarModel extends Model
{
    protected $fillable = [
        'mark_id',
        'category_id',
        'code',
        'name',
    ];
    protected $table = 'car_models';

    public function carMark()
    {
        return $this->belongsTo('App\Models\CarMark');
    }

    public function carCategory()
    {
        return $this->belongsTo('App\Models\CarCategory');
    }

    public function insuranceCodes()
    {
        return $this->belongsToMany('App\Models\CarModelInsurance');
    }
}
