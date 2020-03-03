<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCarInsuranceDataTables extends Migration
{
    private $tables = [
        'Files',
        'PolicyStatuses',
        'InsuranceCompanies',
        'CarMarks',
        'CarModels',
        'PolicyTypes',
        'RegDocTypes',
        'Drivers',
        'Policies',
        'PolicyDriver',
        'Vehicles',
        'ReportTypes',
        'Reports',
        'ReportPolicy',
        'ModelInsurance'
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
        foreach ($this->tables as $tableName) {
            if (method_exists($this, $method = "down{$tableName}")) {
                $this->{$method}();
            }
        }
    }

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

    private function upInsuranceCompanies()
    {
        Schema::create('insurance_companies', function (Blueprint $table){
            $table->integerIncrements('id');
            $table->boolean('active');
            $table->integer('logo_id');
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

    private function upRegDocTypes()
    {
        Schema::create('reg_doc_types', function (Blueprint $table) {
            $table->integerIncrements('id');
            $table->string('code');
            $table->string('name');
            $table->timestamps();
        });
    }

    private function downRegDocTypes()
    {
        Schema::dropIfExists('reg_doc_types');
    }

    private function upDrivers()
    {
        Schema::create('drivers', function (Blueprint $table) {
            $table->integerIncrements('id');
            $table->string('first_name');
            $table->string('last_name');
            $table->string('patronymic')->nullable();
            $table->date('birth_date');
            $table->string('license_series');
            $table->string('license_number');
            $table->string('license_date');
            $table->date('exp_start_date');
            $table->timestamps();
        });
    }

    private function downDrivers()
    {
        Schema::dropIfExists('drivers');
    }

    private function upPolicies()
    {
        Schema::create('policies', function (Blueprint $table) {
            $table->integerIncrements('id');
            $table->boolean('active');
            $table->integer('agent_id');
            $table->integer('client_id');
            $table->string('number');
            $table->integer('insurance_company_id');
            $table->integer('vehicle_model_id');
            $table->integer('vehicle_made_year');
            $table->integer('vehicle_reg_doc_type_id');
            $table->string('vehicle_doc_series');
            $table->string('vehicle_doc_number');
            $table->string('vehicle_vin');
            $table->string('vehicle_category');
            $table->string('vehicle_engine_power');
            $table->string('vehicle_unladen_mass')->nullable();
            $table->string('vehicle_loaded_mass')->nullable();
            $table->string('vehicle_purpose_using')->nullable();
            $table->string('count_seats')->nullable();
            $table->integer('status_id');
            $table->integer('type_id');
            $table->integer('region_id');
            $table->integer('cost');
            $table->integer('commission_id');
            $table->boolean('commission_paid');
            $table->date('registration_date');
            $table->date('start_date');
            $table->date('end_date');
            $table->boolean('paid');
            $table->timestamps();

            $table->foreign('insurance_company_id')->references('id')->on('insurance_companies');
            $table->foreign('vehicle_model_id')->references('id')->on('car_models');
            $table->foreign('vehicle_reg_doc_type_id')->references('id')->on('reg_doc_types');
            $table->foreign('status_id')->references('id')->on('policy_statuses');
            $table->foreign('type_id')->references('id')->on('policy_types');
        });
    }

    private function downPolicies()
    {
        Schema::dropIfExists('policies');
    }

    private function upPolicyDriver()
    {
        Schema::create('driver_policy', function (Blueprint $table) {
            $table->integer('driver_id');
            $table->integer('policy_id');
            $table->timestamps();

            $table->foreign('driver_id')->references('id')->on('drivers');
            $table->foreign('policy_id')->references('id')->on('policies');
        });
    }

    private function downPolicyDriver()
    {
        Schema::dropIfExists('driver_policy');
    }

    private function upVehicles()
    {
        Schema::create('vehicles', function (Blueprint $table) {
            $table->integerIncrements('id');
            $table->string('mark');
            $table->string('model');
            $table->date('made_year');
            $table->string('vin');
            $table->string('category');
            $table->integer('engine_power');
            $table->timestamps();
        });
    }

    private function downVehicles()
    {
        Schema::dropIfExists('vehicles');
    }

    private function upReportTypes()
    {
        Schema::create('report_types', function (Blueprint $table) {
            $table->integerIncrements('id');
            $table->string('code');
            $table->string('name');
            $table->timestamps();
        });
    }

    private function downReportTypes()
    {
        Schema::dropIfExists('report_types');
    }

    private function upReports()
    {
        Schema::create('reports', function (Blueprint $table) {
            $table->integerIncrements('id');
            $table->string('name');
            $table->integer('creator_id');
            $table->integer('report_type_id');
            $table->date('create_date');
            $table->date('period_start');
            $table->date('period_end');
            $table->integer('reward');
            $table->boolean('is_payed');
            $table->timestamps();

            $table->foreign('report_type_id')->references('id')->on('report_types');
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

    private function upCarModels()
    {
        Schema::create('car_models', function (Blueprint $table) {
            $table->integerIncrements('id');
            $table->integer('mark_id');
            $table->string('code');
            $table->string('name');
            $table->timestamps();

            $table->foreign('mark_id')->references('id')->on('car_marks');
        });
    }

    private function downCarModels()
    {
        Schema::dropIfExists('car_models');
    }

    private function upModelInsurance()
    {
        Schema::create('model_insurance', function (Blueprint $table) {
            $table->integer('model_id');
            $table->integer('insurance_company_id');
            $table->string('reference_code');
            $table->timestamps();

            $table->foreign('model_id')->references('id')->on('car_marks');
            $table->foreign('insurance_company_id')->references('id')->on('insurance_companies');
        });
    }

    private function downModelInsurance()
    {
        Schema::dropIfExists('model_insurance');
    }
}
