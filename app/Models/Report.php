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
        'reward',
        'is_payed'
    ];
    protected  $hidden = [
        'created_at',
        'updated_at',
        'file_id',
        'creator_id'
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
