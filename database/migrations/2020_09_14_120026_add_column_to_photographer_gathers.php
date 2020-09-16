<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddColumnToPhotographerGathers extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('photographer_gathers', function (Blueprint $table) {
            //
            $table->integer('sort')->after('status')->default(1)->comment("排序");
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('photographer_gathers', function (Blueprint $table) {
            //
            Schema::dropIfExists('photographer_gathers');
        });
    }
}
