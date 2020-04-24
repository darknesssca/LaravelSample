<?php

namespace App\Models;

use App\Observers\CarModelObserver;
use Illuminate\Database\Eloquent\Model;

class CarModel extends Model
{
    use CarModelObserver;

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
        return $this->hasOne('App\Models\CarCategory', 'id', 'category_id');
    }

    public function codes()
    {
        return $this->hasMany('App\Models\InsuranceModel','model_id', 'id');
    }
}
