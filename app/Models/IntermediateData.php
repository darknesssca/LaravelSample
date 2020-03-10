<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class IntermediateData extends Model
{
    protected $table = 'intermediate_data';
    protected $fillable = [
        'token',
        'data'
    ];
    protected $primaryKey = 'token';
    protected $keyType = 'string';

    public static function createToken($data, $try = 0)
    {
        $token = Str::random(32);
        try {
            self::create([
                'token' => $token,
                'data' => \GuzzleHttp\json_encode($data)
            ]);
            return $token;
        } catch (\Exception $exception) {
            $try += 1;
            if ($try > 5) {
                throw new \Exception('fail create token: '.$exception->getMessage());
            }
            return self::createToken($data, $try);
        }
    }

    public static function getData($token)
    {
        $data = self::find($token);
        if (!$data || !isset($data['form'])) {
            throw new \Exception('not found data by token');
        }
        return $data;
    }
}
