<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class InsuranceModel extends Model
{
    protected $fillable = [
        'model_id',
        'insurance_company_id',
        'reference_model_code',
    ];

    protected $primaryKey = ['model_id','insurance_company_id'];
    public $incrementing = false;
    public $keyType = 'string';


    //---> Illegal offset type while updating model
    //---> because primary key more than 1 --> add this
    //https://laracasts.com/discuss/channels/laravel/illegal-offset-type-while-updating-model?page=1
    protected function setKeysForSaveQuery(Builder $query)
    {
        return $query->where('model_id', $this->getAttribute('model_id'))
            ->where('insurance_company_id', $this->getAttribute('insurance_company_id'));
    }
}
