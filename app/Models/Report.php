<?php

namespace App\Models;

use App\Observers\ReportObserver;
use Benfin\Api\GlobalStorage;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

class Report extends Model
{
    use ReportObserver;

    const STATUS_FAILED = 0;
    const STATUS_PROCESSING = 1;
    const STATUS_PAYED = 2;

    protected $guarded = [];
    protected $table = 'reports';
    protected $fillable = [
        'name',
        'creator_id',
        'create_date',
        'reward',
        'is_payed',
        'creator_id',
        'processing',
    ];
    protected $hidden = [
        'created_at',
        'updated_at',
        'file_id',
    ];

    protected $appends = [
        'status',
    ];

    public function policies()
    {
        return $this->belongsToMany('App\Models\Policy', 'report_policy');
    }

    public function file()
    {
        return $this->belongsTo('App\Models\File');
    }

    public function getStatusAttribute()
    {
        if (
            (
                $this->processing == 0 ||
                ($this->processing > 1000 && $this->processing <= 2000)
            ) &&
            $this->is_payed == false
        ) {
            return self::STATUS_FAILED;
        }

        if (
            ($this->processing > 0 || $this->processing <= 1000) &&
            $this->is_payed == false
        ) {
            return self::STATUS_PROCESSING;
        }

        if ($this->is_payed == true) {
            return self::STATUS_PAYED;
        }

        return null;
    }
}
