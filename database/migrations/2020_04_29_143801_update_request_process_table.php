<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UpdateRequestProcessTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('request_process', function (Blueprint $table) {
            $table->dropColumn('token');
        });
        Schema::table('request_process', function (Blueprint $table) {
            $table->string('token')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('request_process', function (Blueprint $table) {
            $table->dropColumn('token');
        });
        Schema::table('request_process', function (Blueprint $table) {
            $table->string('token')->primary()->unique();
        });
    }
}
