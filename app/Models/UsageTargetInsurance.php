<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class UsageTargetInsurance extends Model
{
    protected $fillable = [
        'target_id',
        'insurance_company_id',
        'reference_usage_target_code',
    ];

    protected $primaryKey = ['target_id','insurance_company_id'];
    public $incrementing = false;
    public $keyType = 'string';


    //---> Illegal offset type while updating model
    //---> because primary key more than 1 --> add this
    //https://laracasts.com/discuss/channels/laravel/illegal-offset-type-while-updating-model?page=1
    protected function setKeysForSaveQuery(Builder $query)
    {
        return $query->where('target_id', $this->getAttribute('target_id'))
            ->where('insurance_company_id', $this->getAttribute('insurance_company_id'));
    }
    protected $table = 'usage_target_insurance';

}
