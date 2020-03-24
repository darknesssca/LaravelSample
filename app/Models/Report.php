<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Report extends Model
{
    protected $guarded = [];
    protected $table = 'reports';
    protected $fillable = [
        'name',
        'creator_id',
        'create_date',
        'reward'
    ];

    public function policies()
    {
        return $this->belongsToMany('App\Models\Policy', 'report_policy');
    }

    public function file()
    {
        return $this->belongsTo('App\Models\File');
    }

}
