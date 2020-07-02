<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UpdatePoliciesTableRemoveVehicleCost extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('policies', function (Blueprint $table) {
            $table->dropColumn('vehicle_cost');
        });

        Schema::table('drafts', function (Blueprint $table) {
            $table->dropColumn('vehicle_cost');
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
            $table->unsignedInteger('vehicle_cost')->nullable();
        });

        Schema::table('drafts', function (Blueprint $table) {
            $table->unsignedInteger('vehicle_cost')->nullable();
        });
    }
}
