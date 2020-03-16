<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Policy extends Model
{
    protected $table = 'policies';

    protected $fillable = [
        'agent_id',
        //'number',
        'insurance_company_id',
        'status_id',
        'type_id',
        'region_id',
        //'premium',
        //'commission_id',
        //'commission_paid',
        //'registration_date',
        //'paid',
        'client_id',
        'insurant_id',
        'vehicle_model_id',
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
        'vehicle_inspection_doc_issued',
        'start_date',
        'end_date',
        'is_multi_drive',
    ];

    public function insuranceCompany()
    {
        return $this->belongsTo('App\Models\InsuranceCompany');
    }

    public function carModel()
    {
        return $this->belongsTo('App\Models\CarModel');
    }

    public function docType()
    {
        return $this->belongsTo('App\Models\CarModel');
    }

    public function policyStatus()
    {
        return $this->belongsTo('App\Models\PolicyStatus');
    }

    public function policyType()
    {
        return $this->belongsTo('App\Models\PolicyType');
    }

    public function draftClients()
    {
        return $this->belongsTo('App\Models\DraftClient');
    }

    public function carRegCountry()
    {
        return $this->belongsTo('App\Models\PolicyType');
    }

    public function carAcquisition()
    {
        return $this->belongsTo('App\Models\PolicyType');
    }

    public function carUsageType()
    {
        return $this->belongsTo('App\Models\UsageType');
    }

    public function carUsageTarget()
    {
        return $this->belongsTo('App\Models\UsageTarget');
    }

    public function drivers()
    {
        return $this->belongsToMany('App\Models\Driver');
    }

    public function reports()
    {
        return $this->belongsToMany('App\Models\Report');
    }

    public function delete() {
        $this->drivers()->delete();
        parent::delete();
    }

    public static function scopeGetPolicies($query, $agentId)
    {
        return self::where('agent_id', $agentId)->get();
    }

    public static function scopeGetPolicyById($query, $id)
    {
        return self::where('id', $id)->get();
    }
}
