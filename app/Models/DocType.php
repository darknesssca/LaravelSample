<?php

namespace App\Models;

use App\Observers\DocTypeObserver;
use Illuminate\Database\Eloquent\Model;

class DocType extends Model
{
    use DocTypeObserver;

    protected $fillable = [
        'code',
        'name',
    ];
    protected $table = 'doc_types';

    public function codes()
    {
        return $this->hasMany('App\Models\DocTypeInsurance', 'doctype_id', 'id');
    }

}
