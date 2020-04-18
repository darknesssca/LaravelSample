<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UpdateDraftsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('drafts', function (Blueprint $table) {
            $table->dropForeign('drafts_vehicle_model_id_foreign');
            $table->dropColumn('vehicle_model_id');

            $table->string('vehicle_model')->nullable();
            $table->integer('vehicle_category_id')->nullable();
            $table->boolean('irregular_vin')->default('false');

            $table->foreign('vehicle_category_id')->references('id')->on('car_categories');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('drafts', function (Blueprint $table) {
            $table->integer('vehicle_model_id');
            $table->foreign('vehicle_model_id')->references('id')->on('car_models');
            $table->dropColumn('vehicle_category_id');
            $table->dropColumn('vehicle_model');
            $table->dropColumn('irregular_vin');
        });
    }
}
