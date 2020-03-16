<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CarCategory extends Model
{
    protected $fillable = [
        'code',
        'name',
    ];
    protected $table = 'car_categories';
}
