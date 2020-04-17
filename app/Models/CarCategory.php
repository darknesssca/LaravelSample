<?php

namespace App\Models;

use App\Observers\CarCategoryObserver;
use Illuminate\Database\Eloquent\Model;

class CarCategory extends Model
{
    use CarCategoryObserver;

    protected $fillable = [
        'code',
        'name',
    ];
    protected $table = 'car_categories';
}
