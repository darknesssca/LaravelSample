<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateDraftsTable extends Migration
{
    public function up()
    {
        Schema::create('drafts', function (Blueprint $table) {
            $table->integerIncrements('id');
            $table->unsignedInteger('agent_id');
            $table->unsignedInteger('type_id')->nullable();
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->boolean('is_multi_drive')->default(false);
            // subject
            $table->unsignedInteger('client_id')->nullable();
            $table->unsignedInteger('insurant_id')->nullable();
            // car
            $table->unsignedInteger('vehicle_model_id')->nullable();
            $table->unsignedInteger('vehicle_engine_power')->nullable();
            $table->string('vehicle_vin')->nullable();
            $table->string('vehicle_reg_number')->nullable();
            $table->unsignedInteger('vehicle_reg_country')->nullable();
            $table->unsignedInteger('vehicle_made_year')->nullable();
            $table->unsignedInteger('vehicle_unladen_mass')->nullable();
            $table->unsignedInteger('vehicle_loaded_mass')->nullable();
            $table->unsignedInteger('vehicle_count_seats')->nullable();
            $table->unsignedInteger('vehicle_mileage')->nullable();
            $table->unsignedInteger('vehicle_cost')->nullable();
            $table->unsignedInteger('vehicle_acquisition')->nullable();
            $table->unsignedInteger('vehicle_usage_target')->nullable();
            $table->boolean('vehicle_with_trailer')->default(false);
            // car.document
            $table->unsignedInteger('vehicle_reg_doc_type_id')->nullable();
            $table->string('vehicle_doc_series')->nullable();
            $table->string('vehicle_doc_number')->nullable();
            $table->date('vehicle_doc_issued')->nullable();
            // car.inspection
            $table->string('vehicle_inspection_doc_series')->nullable();
            $table->string('vehicle_inspection_doc_number')->nullable();
            $table->date('vehicle_inspection_issued_date')->nullable();
            $table->date('vehicle_inspection_end_date')->nullable();

            $table->timestamps();

            $table->foreign('vehicle_model_id')->references('id')->on('car_models');
            $table->foreign('vehicle_reg_doc_type_id')->references('id')->on('doc_types');
            $table->foreign('type_id')->references('id')->on('policy_types');
            $table->foreign('client_id')->references('id')->on('draft_clients');
            $table->foreign('insurant_id')->references('id')->on('draft_clients');
            $table->foreign('vehicle_reg_country')->references('id')->on('countries');
            $table->foreign('vehicle_acquisition')->references('id')->on('source_acquisitions');
            $table->foreign('vehicle_usage_target')->references('id')->on('usage_targets');
        });

        Schema::create('driver_draft', function (Blueprint $table) {
            $table->unsignedInteger('driver_id');
            $table->unsignedInteger('draft_id');
            $table->timestamps();

            $table->foreign('driver_id')->references('id')->on('drivers')->onDelete('cascade');
            $table->foreign('draft_id')->references('id')->on('drafts');
        });

        Schema::table('policies', function (Blueprint $table) {
            $table->dropColumn(['status_id', 'commission_paid']);
            $table->string('region_kladr');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('drafts');
        Schema::dropIfExists('draft_driver');
        Schema::table('policies', function (Blueprint $table) {
            $table->unsignedInteger('status_id');
            $table->boolean('commission_paid')->default(false);
            $table->dropColumn('region_kladr');
            $table->unsignedInteger('region_id')->nullable();
        });
    }
}
