<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class IntermediateData extends Model
{
    protected $table = 'intermediate_data';
    protected $fillable = [
        'token',
        'data'
    ];
    protected $primaryKey = 'token';
    protected $keyType = 'string';
}
