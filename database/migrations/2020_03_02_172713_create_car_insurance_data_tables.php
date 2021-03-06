<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCarInsuranceDataTables extends Migration
{
    private $tables = [
        'Files',
        'InsuranceCompanies',
        'PolicyTypes',
        'PolicyStatuses',
        'DocTypes',
        'DocTypeInsurance',
        'CarMarks',
        'MarkInsurance',
        'CarCategories',
        'CarModels',
        'ModelInsurance',
        'Country',
        'SourceAcquisition',
        'AcquisitionInsurance',
        'UsageTargets',
        'UsageTargetInsurance',
        'Gender',
        'GenderInsurance',
        'DraftClient',
        'Policies',
        'BillPolicy',
        'Drivers',
        'PolicyDriver',
        'Reports',
        'ReportPolicy',
    ];
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        foreach ($this->tables as $tableName) {
            if (method_exists($this, $method = "up{$tableName}")) {
                $this->{$method}();
            }
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        $this->tables = array_reverse($this->tables);
        foreach ($this->tables as $tableName) {
            if (method_exists($this, $method = "down{$tableName}")) {
                $this->{$method}();
            }
        }
    }

    // страховые компании
    private function upFiles()
    {
        Schema::create('files', function (Blueprint $table) {
            $table->integerIncrements('id');
            $table->string('name');
            $table->string('dir');
            $table->string('content_type');
            $table->integer('size');
            $table->timestamps();
        });
    }

    private function downFiles()
    {
        Schema::dropIfExists('files');
    }

    private function upInsuranceCompanies()
    {
        Schema::create('insurance_companies', function (Blueprint $table){
            $table->integerIncrements('id');
            $table->boolean('active');
            $table->unsignedInteger('logo_id');
            $table->string('code');
            $table->string('name');
            $table->timestamps();

            $table->foreign('logo_id')->references('id')->on('files');
        });
    }

    private function downInsuranceCompanies()
    {
        Schema::dropIfExists('insurance_companies');
    }

    // справочники
    // тип полиса
    private function upPolicyTypes()
    {
        Schema::create('policy_types', function (Blueprint $table){
            $table->integerIncrements('id');
            $table->string('name');
            $table->string('code');
            $table->timestamps();
        });
    }

    private function downPolicyTypes()
    {
        Schema::dropIfExists('policy_types');
    }

    // статус полиса
    private function upPolicyStatuses()
    {
        Schema::create('policy_statuses', function (Blueprint $table) {
            $table->integerIncrements('id');
            $table->boolean('active');
            $table->string('code');
            $table->string('name');
            $table->timestamps();
        });
    }

    private function downPolicyStatuses()
    {
        Schema::dropIfExists('policy_statuses');
    }

    // тип документа
    private function upDocTypes()
    {
        Schema::create('doc_types', function (Blueprint $table) {
            $table->integerIncrements('id');
            $table->string('code');
            $table->string('name');
            $table->timestamps();
        });
    }

    private function downDocTypes()
    {
        Schema::dropIfExists('doc_types');
    }

    private function upDocTypeInsurance()
    {
        Schema::create('doctype_insurance', function (Blueprint $table) {
            $table->unsignedInteger('doctype_id');
            $table->unsignedInteger('insurance_company_id');
            $table->string('reference_doctype_code');
            $table->string('reference_doctype_code2');
            $table->string('reference_doctype_code3');
            $table->timestamps();

            $table->foreign('doctype_id')->references('id')->on('doc_types')->onDelete('cascade');
            $table->foreign('insurance_company_id')->references('id')->on('insurance_companies');
        });
    }

    private function downDocTypeInsurance()
    {
        Schema::dropIfExists('doctype_insurance');
    }

    // марка автомобиля
    private function upCarMarks()
    {
        Schema::create('car_marks', function (Blueprint $table) {
            $table->integerIncrements('id');
            $table->string('code');
            $table->string('name');
            $table->timestamps();
        });
    }

    private function downCarMarks()
    {
        Schema::dropIfExists('car_marks');
    }

    private function upMarkInsurance()
    {
        Schema::create('insurance_marks', function (Blueprint $table) {
            $table->unsignedInteger('mark_id');
            $table->unsignedInteger('insurance_company_id');
            $table->string('reference_mark_code');
            $table->timestamps();

            $table->foreign('mark_id')->references('id')->on('car_marks')->onDelete('cascade');
            $table->foreign('insurance_company_id')->references('id')->on('insurance_companies');
        });
    }

    private function downMarkInsurance()
    {
        Schema::dropIfExists('insurance_marks');
    }

    // категория автомобиля
    private function upCarCategories()
    {
        Schema::create('car_categories', function (Blueprint $table) {
            $table->integerIncrements('id');
            $table->string('code');
            $table->string('name');
            $table->timestamps();
        });
    }

    private function downCarCategories()
    {
        Schema::dropIfExists('car_categories');
    }

    // модель автомобиля
    private function upCarModels()
    {
        Schema::create('car_models', function (Blueprint $table) {
            $table->integerIncrements('id');
            $table->unsignedInteger('mark_id');
            $table->unsignedInteger('category_id');
            $table->string('code');
            $table->string('name');
            $table->timestamps();

            $table->foreign('mark_id')->references('id')->on('car_marks')->onDelete('cascade');
            $table->foreign('category_id')->references('id')->on('car_categories')->onDelete('cascade');
        });
    }

    private function downCarModels()
    {
        Schema::dropIfExists('car_models');
    }

    private function upModelInsurance()
    {
        Schema::create('insurance_models', function (Blueprint $table) {
            $table->unsignedInteger('model_id');
            $table->unsignedInteger('insurance_company_id');
            $table->string('reference_model_code');
            $table->timestamps();

            $table->foreign('model_id')->references('id')->on('car_models')->onDelete('cascade');
            $table->foreign('insurance_company_id')->references('id')->on('insurance_companies');
        });
    }

    private function downModelInsurance()
    {
        Schema::dropIfExists('insurance_models');
    }

    // страна регистрации
    private function upCountry()
    {
        Schema::create('countries', function (Blueprint $table) {
            $table->integerIncrements('id');
            $table->integer('code');
            $table->string('name');
            $table->string('short_name');
            $table->string('alpha2');
            $table->string('alpha3');
            $table->timestamps();
        });
    }

    private function downCountry()
    {
        Schema::dropIfExists('countries');
    }

    // способ приобретения автомобиля
    private function upSourceAcquisition()
    {
        Schema::create('source_acquisitions', function (Blueprint $table) {
            $table->integerIncrements('id');
            $table->string('code');
            $table->string('name');
            $table->timestamps();
        });
    }

    private function downSourceAcquisition()
    {
        Schema::dropIfExists('source_acquisitions');
    }

    private function upAcquisitionInsurance()
    {
        Schema::create('acquisition_insurance', function (Blueprint $table) {
            $table->unsignedInteger('acquisition_id');
            $table->unsignedInteger('insurance_company_id');
            $table->string('reference_acquisition_code');
            $table->timestamps();

            $table->foreign('acquisition_id')->references('id')->on('source_acquisitions')->onDelete('cascade');
            $table->foreign('insurance_company_id')->references('id')->on('insurance_companies');
        });
    }

    private function downAcquisitionInsurance()
    {
        Schema::dropIfExists('acquisition_insurance');
    }

    // цель использования
    private function upUsageTargets()
    {
        Schema::create('usage_targets', function (Blueprint $table) {
            $table->integerIncrements('id');
            $table->string('code');
            $table->string('name');
            $table->timestamps();
        });
    }

    private function downUsageTargets()
    {
        Schema::dropIfExists('usage_targets');
    }

    private function upUsageTargetInsurance()
    {
        Schema::create('usage_target_insurance', function (Blueprint $table) {
            $table->unsignedInteger('target_id');
            $table->unsignedInteger('insurance_company_id');
            $table->string('reference_usage_target_code');
            $table->timestamps();

            $table->foreign('target_id')->references('id')->on('usage_targets')->onDelete('cascade');
            $table->foreign('insurance_company_id')->references('id')->on('insurance_companies');
        });
    }

    private function downUsageTargetInsurance()
    {
        Schema::dropIfExists('usage_target_insurance');
    }

    // пол
    private function upGender()
    {
        Schema::create('genders', function (Blueprint $table) {
            $table->integerIncrements('id');
            $table->string('code');
            $table->string('name');
            $table->timestamps();
        });
    }

    private function downGender()
    {
        Schema::dropIfExists('genders');
    }

    private function upGenderInsurance()
    {
        Schema::create('gender_insurance', function (Blueprint $table) {
            $table->unsignedInteger('gender_id');
            $table->unsignedInteger('insurance_company_id');
            $table->string('reference_gender_code');
            $table->timestamps();

            $table->foreign('gender_id')->references('id')->on('genders');
            $table->foreign('insurance_company_id')->references('id')->on('insurance_companies');
        });
    }

    private function downGenderInsurance()
    {
        Schema::dropIfExists('gender_insurance');
    }


    // полисы
    private function upDraftClient()
    {
        Schema::create('draft_clients', function (Blueprint $table) {
            $table->integerIncrements('id');
            $table->string('last_name')->nullable();
            $table->string('first_name')->nullable();
            $table->string('patronymic')->nullable();
            $table->unsignedInteger('gender_id')->nullable();
            $table->date('birth_date')->nullable();
            $table->string('birth_place')->nullable();
            $table->string('passport_series')->nullable();
            $table->string('passport_number')->nullable();
            $table->date('passport_date')->nullable();
            $table->string('passport_issuer')->nullable();
            $table->string('passport_unit_code')->nullable();
            $table->string('address')->nullable();
            $table->string('phone')->nullable();
            $table->string('email')->nullable();
            $table->unsignedInteger('citizenship_id')->nullable();
            $table->boolean('is_russian')->default(true);
            $table->timestamps();

            $table->foreign('gender_id')->references('id')->on('genders');
            $table->foreign('citizenship_id')->references('id')->on('countries');
        });
    }

    private function downDraftClient()
    {
        Schema::dropIfExists('draft_clients');
    }

    private function upPolicies()
    {
        Schema::create('policies', function (Blueprint $table) {
            // base
            $table->integerIncrements('id');
            $table->unsignedInteger('agent_id');
            $table->string('number')->nullable();
            $table->unsignedInteger('insurance_company_id');
            $table->unsignedInteger('status_id');
            $table->unsignedInteger('type_id')->nullable();
            $table->unsignedInteger('region_id')->nullable();
            $table->double('premium')->nullable();
            $table->unsignedInteger('commission_id')->nullable();
            $table->boolean('commission_paid')->default(false);
            $table->date('registration_date');
            $table->boolean('paid')->default(false);
            $table->date('start_date');
            $table->date('end_date');
            $table->boolean('is_multi_drive')->default(false);
            // subject
            $table->unsignedInteger('client_id');
            $table->unsignedInteger('insurant_id');
            // car
            $table->unsignedInteger('vehicle_model_id');
            $table->unsignedInteger('vehicle_engine_power');
            $table->string('vehicle_vin');
            $table->string('vehicle_reg_number');
            $table->unsignedInteger('vehicle_reg_country');
            $table->unsignedInteger('vehicle_made_year');
            $table->unsignedInteger('vehicle_unladen_mass')->nullable();
            $table->unsignedInteger('vehicle_loaded_mass')->nullable();
            $table->unsignedInteger('vehicle_count_seats')->nullable();
            $table->unsignedInteger('vehicle_mileage');
            $table->unsignedInteger('vehicle_cost');
            $table->unsignedInteger('vehicle_acquisition');
            $table->unsignedInteger('vehicle_usage_target');
            $table->boolean('vehicle_with_trailer')->default(false);
            // car.document
            $table->unsignedInteger('vehicle_reg_doc_type_id');
            $table->string('vehicle_doc_series')->nullable();
            $table->string('vehicle_doc_number');
            $table->date('vehicle_doc_issued');
            // car.inspection
            $table->string('vehicle_inspection_doc_series')->nullable();
            $table->string('vehicle_inspection_doc_number');
            $table->date('vehicle_inspection_issued_date');
            $table->date('vehicle_inspection_end_date');

            $table->timestamps();

            $table->foreign('insurance_company_id')->references('id')->on('insurance_companies');
            $table->foreign('vehicle_model_id')->references('id')->on('car_models');
            $table->foreign('vehicle_reg_doc_type_id')->references('id')->on('doc_types');
            $table->foreign('status_id')->references('id')->on('policy_statuses');
            $table->foreign('type_id')->references('id')->on('policy_types');
            $table->foreign('client_id')->references('id')->on('draft_clients');
            $table->foreign('insurant_id')->references('id')->on('draft_clients');
            $table->foreign('vehicle_reg_country')->references('id')->on('countries');
            $table->foreign('vehicle_acquisition')->references('id')->on('source_acquisitions');
            $table->foreign('vehicle_usage_target')->references('id')->on('usage_targets');
        });
    }

    private function downPolicies()
    {
        Schema::dropIfExists('policies');
    }

    private function upBillPolicy()
    {
        Schema::create('bill_policy', function (Blueprint $table) {
            $table->unsignedInteger('policy_id');
            $table->string('bill_id');
            $table->timestamps();

            $table->foreign('policy_id')->references('id')->on('policies');
        });
    }

    private function downBillPolicy()
    {
        Schema::dropIfExists('bill_policy');
    }

    private function upDrivers()
    {
        Schema::create('drivers', function (Blueprint $table) {
            $table->integerIncrements('id');
            $table->string('first_name')->nullable();
            $table->string('last_name')->nullable();
            $table->string('patronymic')->nullable();
            $table->date('birth_date')->nullable();
            $table->string('license_series')->nullable();
            $table->string('license_number')->nullable();
            $table->date('license_date')->nullable();
            $table->date('exp_start_date')->nullable();
            $table->timestamps();
        });
    }

    private function downDrivers()
    {
        Schema::dropIfExists('drivers');
    }

    private function upPolicyDriver()
    {
        Schema::create('driver_policy', function (Blueprint $table) {
            $table->unsignedInteger('driver_id');
            $table->unsignedInteger('policy_id');
            $table->timestamps();

            $table->foreign('driver_id')->references('id')->on('drivers')->onDelete('cascade');
            $table->foreign('policy_id')->references('id')->on('policies');
        });
    }

    private function downPolicyDriver()
    {
        Schema::dropIfExists('driver_policy');
    }

    // репорты
    private function upReports()
    {
        Schema::create('reports', function (Blueprint $table) {
            $table->integerIncrements('id');
            $table->string('name');
            $table->integer('creator_id');
            $table->date('create_date');
            $table->integer('reward');
            $table->boolean('is_payed')->default(false);
            $table->timestamps();
        });
    }

    private function downReports()
    {
        Schema::dropIfExists('reports');
    }

    private function upReportPolicy()
    {
        Schema::create('report_policy', function (Blueprint $table) {
            $table->integer('report_id');
            $table->integer('policy_id');
            $table->timestamps();

            $table->foreign('report_id')->references('id')->on('reports');
            $table->foreign('policy_id')->references('id')->on('policies');
        });
    }

    private function downReportPolicy()
    {
        Schema::dropIfExists('report_policy');
    }

}
