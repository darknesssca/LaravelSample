<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddGuidesCompositePkey extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('insurance_marks', function (Blueprint $table) {
            $table->primary(array('mark_id', 'insurance_company_id'));
        });
        Schema::table('insurance_models', function (Blueprint $table) {
            $table->primary(array('model_id', 'insurance_company_id'));
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('insurance_marks', function (Blueprint $table) {
            $table->dropPrimary(array('mark_id', 'insurance_company_id'));
        });
        Schema::table('insurance_models', function (Blueprint $table) {
            $table->dropPrimary(array('model_id', 'insurance_company_id'));
        });
    }
}
