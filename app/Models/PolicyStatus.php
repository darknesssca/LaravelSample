<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PolicyStatus extends Model
{
    protected $fillable = [
        'active',
        'code',
        'name',
    ];
    protected $table = 'policy_statuses';
}
