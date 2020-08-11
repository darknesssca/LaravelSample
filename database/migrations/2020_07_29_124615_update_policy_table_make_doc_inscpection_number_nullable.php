<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UpdatePolicyTableMakeDocInscpectionNumberNullable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('policies', function(Blueprint $table) {
            $table->string('vehicle_inspection_doc_number')->nullable()->change();
            $table->date('vehicle_inspection_issued_date')->nullable()->change();
            $table->date('vehicle_inspection_end_date')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('policies', function(Blueprint $table) {
            $table->string('vehicle_inspection_doc_number')->change();
            $table->date('vehicle_inspection_issued_date')->change();
            $table->date('vehicle_inspection_end_date')->change();
        });
    }
}
