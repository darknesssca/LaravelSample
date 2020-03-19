<?php

namespace App\Models;

use Carbon\Carbon;
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

    public function company()
    {
        return $this->belongsTo('App\Models\InsuranceCompany');
    }

    public function model()
    {
        return $this->belongsTo('App\Models\CarModel', 'vehicle_model_id', 'id');
    }

    public function doctype()
    {
        return $this->belongsTo('App\Models\DocType', 'vehicle_reg_doc_type_id', 'id');
    }

    public function status()
    {
        return $this->belongsTo('App\Models\PolicyStatus');
    }

    public function type()
    {
        return $this->belongsTo('App\Models\PolicyType');
    }

    public function owner()
    {
        return $this->belongsTo('App\Models\DraftClient', 'client_id', 'id');
    }

    public function insurer()
    {
        return $this->belongsTo('App\Models\DraftClient', 'insurant_id', 'id');
    }

    public function regcountry()
    {
        return $this->belongsTo('App\Models\PolicyType', 'vehicle_reg_country', 'id');
    }

    public function acquisition()
    {
        return $this->belongsTo('App\Models\PolicyType', 'vehicle_acquisition', 'id');
    }

    public function usagetype()
    {
        return $this->belongsTo('App\Models\UsageType', 'vehicle_usage_target', 'id');
    }

    public function usagetarget()
    {
        return $this->belongsTo('App\Models\UsageTarget', 'vehicle_usage_type', 'id');
    }

    public function drivers()
    {
        return $this->belongsToMany('App\Models\Driver');
    }

    public function delete() {
//        $this->drivers()->delete();
        $this->owner()->delete();
        $this->insurer()->delete();
        parent::delete();
    }

    public static function scopeGetPolicies($query, $agentId)
    {
        return self::with([
            'model',
            'model.mark',
            'model.category',
            'doctype',
            'status',
            'type',
            'owner',
            'owner.gender',
            'owner.citizenship',
            'insurer',
            'insurer.gender',
            'insurer.citizenship',
            'regcountry',
            'acquisition',
            'usagetype',
            'usagetarget',
            'drivers',
        ])->where('agent_id', $agentId)->get();
    }

    public static function scopeGetPolicyById($query, $agentId, $id)
    {
        return self::with([
            'model',
            'model.mark',
            'model.category',
            'doctype',
            'status',
            'type',
            'owner',
            'owner.gender',
            'owner.citizenship',
            'insurer',
            'insurer.gender',
            'insurer.citizenship',
            'regcountry',
            'acquisition',
            'usagetype',
            'usagetarget',
            'drivers',
        ])
            ->where('agent_id', $agentId)
            ->where('id', $id)->get()
            ->first();
    }
}
