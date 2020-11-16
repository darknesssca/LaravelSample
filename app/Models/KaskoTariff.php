<?php


namespace App\Models;


use App\Observers\KaskoTariffObserver;
use Illuminate\Database\Eloquent\Model;

class KaskoTariff extends Model
{
    use KaskoTariffObserver;

    protected $fillable = [
        'name',
        'active',
        'description'
    ];

    protected $table = 'kasko_tariffs';

    public function company()
    {
        return $this->belongsTo('App\Models\InsuranceCompany', 'insurance_company_id', 'id');
    }
}
