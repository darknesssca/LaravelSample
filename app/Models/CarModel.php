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

    public function mark()
    {
        return $this->belongsTo('App\Models\CarMark');
    }

    public function category()
    {
        return $this->belongsTo('App\Models\CarCategory');
    }

    public function company()
    {
        return $this->belongsToMany('App\Models\CarModelInsurance');
    }
}
