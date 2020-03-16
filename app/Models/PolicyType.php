<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PolicyType extends Model
{
    protected $fillable = [
        'name',
        'code',
    ];
    protected $table = 'policy_types';
}
