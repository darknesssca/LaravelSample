<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

class IntermediateData extends Model
{
    protected $table = 'intermediate_data';
    protected $fillable = [
        'hash',
        'data'
    ];
    protected $primaryKey = 'hash';
    protected $keyType = 'string';
}
