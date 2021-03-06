<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DraftClient extends Model
{
    protected $fillable = [
        'last_name',
        'first_name',
        'patronymic',
        'gender_id',
        'birth_date',
        'birth_place',
        'passport_series',
        'passport_number',
        'passport_date',
        'passport_issuer',
        'passport_unit_code',
        'address',
        'address_json',
        'phone',
        'email',
        'citizenship_id',
        'is_russian',
    ];

    protected $table = 'draft_clients';

    protected $casts = [
        'address_json' => 'array'
    ];

    public function policy()
    {
        return $this->belongsToMany('App\Models\Policy');
    }

    public function gender()
    {
        return $this->belongsTo('App\Models\Gender');
    }

    public function citizenship()
    {
        return $this->belongsTo('App\Models\Country');
    }

    /**
     * Мутатор для email-адреса
     * @param $value
     */
    public function setEmailAttribute($value)
    {
        $this->attributes['email'] = strtolower($value);
    }
}
