<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterTablePhotographerGathersAddColumns extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        //
        Schema::table('photographer_gathers', function (Blueprint $table) {
            $table->integer("type")->default(1)->comment("合集类型");
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        //
        Schema::table('photographer_gathers', function (Blueprint $table) {
            //
            Schema::dropIfExists('photographer_gathers');
        });
    }
}
