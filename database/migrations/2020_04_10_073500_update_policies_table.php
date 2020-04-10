<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UpdatePoliciesTable extends Migration
{
    public function up()
    {
        Schema::table('policies', function (Blueprint $table) {
            $table->dropForeign([
                'client_id',
                'policies_inusrant',
                'model_id',
            ]);
            $table->dropColumn(['region_id', 'vehicle_model_id']);
            $table->string('vehicle_model');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('policies', function (Blueprint $table) {
            $table->unsignedInteger('region_id')->nullable();
            $table->unsignedInteger('vehicle_model_id')->nullable();
            $table->foreign('vehicle_model_id')->references('id')->on('car_models');
            $table->foreign('client_id')->references('id')->on('draft_clients');
            $table->foreign('insurant_id')->references('id')->on('draft_clients');
        });
    }
}
