<?php

namespace App\Models;

use App\Observers\GenderObserver;
use Illuminate\Database\Eloquent\Model;

class Gender extends Model
{
    use GenderObserver;

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
