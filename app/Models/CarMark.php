<?php

namespace App\Models;


use App\Observers\CarMarkObserver;
use Illuminate\Database\Eloquent\Model;

class CarMark extends Model
{
    use CarMarkObserver;

    protected $fillable = [
        'code',
        'name',
    ];
    protected $table = 'car_marks';

    public function codes()
    {
        return $this->hasMany('App\Models\InsuranceMark','mark_id', 'id');
    }
}
