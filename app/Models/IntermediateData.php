<?php

namespace App\Models;


use Illuminate\Database\Eloquent\Model;

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
