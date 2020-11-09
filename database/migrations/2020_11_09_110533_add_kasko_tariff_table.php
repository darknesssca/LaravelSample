<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddKaskoTariffTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('kasko_tariffs', function (Blueprint $table){
            $table->integerIncrements('id');
            $table->boolean('active');
            $table->unsignedInteger('insurance_company_id');
            $table->string('name');
            $table->string('ref_code');
            $table->text('description');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('kasko_tariffs');
    }
}
