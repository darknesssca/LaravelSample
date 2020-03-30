<?php

namespace App\Models;

use Benfin\Api\GlobalStorage;
use Carbon\Carbon;
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

    public static function boot()
    {
        parent::boot();
        self::creating(function ($model) {
            $model->create_date = Carbon::now();
            $model->creator_id = GlobalStorage::getUserId();
            $model->is_payed = false;
        });
    }
}
