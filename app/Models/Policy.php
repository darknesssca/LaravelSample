<?php

namespace App\Models;

use App\Observers\PolicyObserver;
use Illuminate\Database\Eloquent\Model;

class Policy extends Model
{
    use PolicyObserver;

    protected $table = 'policies';

    protected $fillable = [
        'agent_id',
        'number',
        'insurance_company_id',
        'status_id',
        'type_id',
        'region_kladr',
        'premium',
        //'commission_id',
        //'commission_paid',
        //'registration_date',
        'paid',
        'client_id',
        'insurant_id',
        'vehicle_model_id',
        'vehicle_model',
        'vehicle_reg_number',
        'vehicle_engine_power',
        'vehicle_vin',
        'vehicle_reg_country',
        'vehicle_made_year',
        'vehicle_unladen_mass',
        'vehicle_loaded_mass',
        'vehicle_count_seats',
        'vehicle_mileage',
        'vehicle_cost',
        'vehicle_acquisition',
        'vehicle_usage_target',
        'vehicle_usage_type',
        'vehicle_with_trailer',
        'vehicle_reg_doc_type_id',
        'vehicle_doc_series',
        'vehicle_doc_number',
        'vehicle_doc_issued',
        'vehicle_inspection_doc_series',
        'vehicle_inspection_doc_number',
        'vehicle_inspection_issued_date',
        'vehicle_inspection_end_date',
        'start_date',
        'end_date',
        'is_multi_drive',
    ];

    protected $hidden = [
        'pivot'
    ];

    public function company()
    {
        return $this->belongsTo('App\Models\InsuranceCompany', 'insurance_company_id', 'id');
    }

    public function model()
    {
        return $this->belongsTo('App\Models\CarModel', 'vehicle_model_id', 'id');
    }

    public function doctype()
    {
        return $this->belongsTo('App\Models\DocType', 'vehicle_reg_doc_type_id', 'id');
    }

    public function type()
    {
        return $this->belongsTo('App\Models\PolicyType');
    }

    public function regcountry()
    {
        return $this->belongsTo('App\Models\PolicyType', 'vehicle_reg_country', 'id');
    }

    public function acquisition()
    {
        return $this->belongsTo('App\Models\PolicyType', 'vehicle_acquisition', 'id');
    }

    public function usagetarget()
    {
        return $this->belongsTo('App\Models\UsageTarget', 'vehicle_usage_target', 'id');
    }

    public function drivers()
    {
        return $this->belongsToMany('App\Models\Driver');
    }

    public function bill()
    {
        return $this->hasOne('App\Models\BillPolicy', 'policy_id', 'id');
    }

    public function reports()
    {
        return $this->belongsToMany('App\Models\Report', 'report_policy');
    }
}
