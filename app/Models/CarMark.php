<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CarMark extends Model
{
    protected $fillable = [
        'mark_id',
        'code',
        'name',
    ];
    protected $table = 'car_marks';

    public function insuranceCodes()
    {
        return $this->belongsToMany('App\Models\CarMarkInsurance');
    }

}
