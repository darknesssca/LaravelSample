<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class ChangeProcessingReportField extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('reports', function (Blueprint $table){
            /**
             * 1-1000           processing states
             * 1001-2000        error states
             * 2001+            reserved states
             */
            $table->unsignedInteger('processing')->default(1)->change();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('reports', function (Blueprint $table){
            $table->boolean('processing')->default(true)->change();
        });
    }
}
