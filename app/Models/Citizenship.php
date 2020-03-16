<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Citizenship extends Model
{
    protected $fillable = [
        'code',
        'name',
    ];
    protected $table = 'citizenship';

}
