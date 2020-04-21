<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class RemoveCascadeDeleteOnModels extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('car_models',function (Blueprint $table) {
            $table->dropForeign('car_models_category_id_foreign');
            $table->dropForeign('car_models_mark_id_foreign');
            $table->foreign('mark_id')->references('id')->on('car_marks');
            $table->foreign('category_id')->references('id')->on('car_categories');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('car_models',function (Blueprint $table) {
            $table->dropForeign('car_models_category_id_foreign');
            $table->dropForeign('car_models_mark_id_foreign');
            $table->foreign('mark_id')->references('id')->on('car_marks')->onDelete('cascade');
            $table->foreign('category_id')->references('id')->on('car_categories')->onDelete('cascade');
        });

    }
}
