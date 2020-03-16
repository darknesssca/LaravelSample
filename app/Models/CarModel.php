<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CarModel extends Model
{
    protected $fillable = [
        'mark_id',
        'code',
        'name',
    ];
    protected $table = 'car_models';

    public function carMark()
    {
        return $this->belongsTo('App\Models\CarMark');
    }
}
