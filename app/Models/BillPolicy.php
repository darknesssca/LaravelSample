<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BillPolicy extends Model
{
    protected $fillable = [
        'policy_id',
        'bill_id',
    ];
    protected $table = 'bill_policy';
    protected $primaryKey = null;
    public $incrementing = false;

    public function policy()
    {
        return $this->belongsTo('App\Models\Policy');
    }
}
