<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UpdateDriversTableAddFields extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('drivers', function (Blueprint $table) {
            $table->integer('citizenship_id')->nullable();
            $table->integer('gender_id')->nullable();

            $table->foreign('citizenship_id')->references('id')->on('countries');
            $table->foreign('gender_id')->references('id')->on('genders');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('drivers', function (Blueprint $table) {
            $table->dropForeign('drivers_citizenship_id_foreign');
            $table->dropForeign('drivers_gender_id_foreign');

            $table->dropColumn('citizenship_id');
            $table->dropColumn('gender_id');
        });
    }
}
