<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateRequestProcessTable extends Migration
{
    /**
     *
     * state - числовой идентификатор текущего шага работы заявления
     *      1 -
     *      2 -
     *      3 -
     *      4 -
     *      5 -
     *      6 -
     *      7 -
     *      9 -
     *      50 - processing | получение данных о созданой заявке
     *      75 - hold | получение ссылки
     *      100 - получение данных об оплате
     *
     *
     * @return void
     */
    public function up()
    {
        Schema::create('request_process', function (Blueprint $table) {
            $table->string('token')->primary()->unique();
            $table->string('company');
            $table->unsignedInteger('state')->default(1);
            $table->unsignedInteger('checkCount')->default(0);
            $table->json('data');
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
        Schema::dropIfExists('request_process');
    }
}
