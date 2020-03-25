<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class RequestProcess extends Model
{
    protected $table = 'request_process';
    protected $fillable = [
        'token',
        'state',
        'data',
        'company',
        'checkCount'
    ];
    protected $primaryKey = 'token';
    protected $keyType = 'string';

    public static function updateCheckCount($token)
    {
        $data = self::where('token', $token)->first();
        if (!$data) {
            return true;
        }
        $checkCount = ++$data->checkCount;
        if ($checkCount >= config('api_sk.maxCheckCount')) {
            self::where('token', $token)->delete();
            return false;
        }
        self::where('token', $token)->update(['checkCount' => $checkCount]);
        return true;
    }

}
