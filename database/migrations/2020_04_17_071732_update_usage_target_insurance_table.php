<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UpdateUsageTargetInsuranceTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('usage_target_insurance', function (Blueprint $table) {
            $table->string('reference_usage_target_code2')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('usage_target_insurance', function (Blueprint $table) {
            $table->dropColumn(['reference_usage_target_code2']);
        });
    }
}
