<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class File extends Model
{
    protected $fillable = [
        'name',
        'dir',
        'content_type',
        'size',
    ];
    protected $table = 'files';

}
