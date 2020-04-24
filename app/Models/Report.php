<?php

namespace App\Models;

use App\Observers\ReportObserver;
use Benfin\Api\GlobalStorage;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

class Report extends Model
{
    use ReportObserver;

    protected $guarded = [];
    protected $table = 'reports';
    protected $fillable = [
        'name',
        'creator_id',
        'create_date',
        'reward',
        'is_payed'
    ];
    protected $hidden = [
        'created_at',
        'updated_at',
        'file_id',
        'creator_id'
    ];

    protected $appends = [
        'create_payout_link',
        'execute_payout_link'
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

    //Accessors

    public function getCreatePayoutLinkAttribute()
    {
            return ($this->requested == false && $this->is_payed == false) ? "/api/v1/car-insurance/reports/{$this->id}/payout/create" : '';
    }

    public function getExecutePayoutLinkAttribute()
    {
        return ($this->is_payed == false && $this->requested == true) ? "/api/v1/car-insurance/reports/{$this->id}/payout/execute" : '';
    }
}
