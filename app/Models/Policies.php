<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

class Policies extends Model
{
    protected $table = 'insurance_companies';

    public function getRouteKey()
    {
        return 'code';
    }

    public static function scopeGetPolicies($query, $agentId)
    {

    }
}
