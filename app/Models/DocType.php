<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DocType extends Model
{
    protected $fillable = [
        'code',
        'name',
    ];
    protected $table = 'doc_types';

}
