<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SourceAcquisition extends Model
{
    protected $fillable = [
        'code',
        'name',
    ];
    protected $table = 'source_acquisitions';

    public function codes()
    {
        return $this->hasMany('App\Models\SourceAcquisitionInsurance', 'acquisition_id', 'id');
    }
}
