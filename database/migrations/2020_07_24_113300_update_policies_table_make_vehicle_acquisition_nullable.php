<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UpdatePoliciesTableMakeVehicleAcquisitionNullable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('policies', function (Blueprint $table) {
            $table->unsignedInteger('vehicle_acquisition')->nullable()->change();
            $table->unsignedInteger('vehicle_mileage')->nullable()->change();
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
            $table->unsignedInteger('vehicle_acquisition')->change();
            $table->unsignedInteger('vehicle_mileage')->change();
        });
    }
}
