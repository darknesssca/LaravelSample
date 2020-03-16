<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DraftClient extends Model
{
    protected $fillable = [
        'last_name',
        'first_name',
        'patronymic',
        'birth_date',
        'passport_series',
        'passport_number',
        'passport_date',
        'passport_issuer',
        'passport_unit_code',
        'address',
        'is_russian',
    ];
    protected $table = 'draft_clients';

    public function policy()
    {
        return $this->belongsToMany('App\Models\Policy');
    }

}
