<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class RequestProcess extends Model
{
    protected $table = 'request_process';
    protected $fillable = [
        'token',
        'state',
        'data'
    ];
    protected $primaryKey = 'token';
    protected $keyType = 'string';

}
