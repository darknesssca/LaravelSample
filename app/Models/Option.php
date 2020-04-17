<?php


namespace App\Models;


use App\Observers\OptionObserver;
use Illuminate\Database\Eloquent\Model;

class Option extends Model
{
    use OptionObserver;

    protected $fillable = [
        'code',
        'name',
        'value',
    ];

    protected $hidden = [
        'created_at',
        'updated_at'
    ];
}
