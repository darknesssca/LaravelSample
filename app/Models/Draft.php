<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Draft extends Model
{
    protected $table = 'drafts';

    protected $fillable = [
        'agent_id',
        'type_id',
        'client_id',
        'insurant_id',
        'vehicle_model',
        'vehicle_category_id',
        'vehicle_mark_id',
        'vehicle_engine_power',
        'vehicle_vin',
        'vehicle_irregular_vin',
        'vehicle_reg_country',
        'vehicle_reg_number',
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
        'vehicle_inspection_issued_date',
        'vehicle_inspection_end_date',
        'start_date',
        'end_date',
        'is_multi_drive',
    ];


    public function company()
    {
        return $this->belongsTo('App\Models\InsuranceCompany');
    }

    public function mark()
    {
        return $this->belongsTo('App\Models\CarMark', 'vehicle_mark_id', 'id');
    }

    public function category()
    {
        return $this->belongsTo('App\Models\CarCategory', 'vehicle_category_id', 'id');
    }

    public function doctype()
    {
        return $this->belongsTo('App\Models\DocType', 'vehicle_reg_doc_type_id', 'id');
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
        return $this->belongsTo('App\Models\Country', 'vehicle_reg_country', 'id');
    }

    public function acquisition()
    {
        return $this->belongsTo('App\Models\SourceAcquisition', 'vehicle_acquisition', 'id');
    }

    public function usagetarget()
    {
        return $this->belongsTo('App\Models\UsageTarget', 'vehicle_usage_target', 'id');
    }

    public function drivers()
    {
        return $this->belongsToMany('App\Models\Driver');
    }

    public function delete()
    {
        parent::delete();
        $this->owner()->delete();
        $this->insurer()->delete();
        $this->drivers()->delete();
    }
}
