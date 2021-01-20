<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterTablePhotographerWorksChangeColumns extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        //
        Schema::table('photographer_works', function (Blueprint $table) {
            $table->integer("project_amount")->nullable(true)->comment("拍摄金额")->change();

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
        Schema::table('photographer_works', function (Blueprint $table) {
            //
            Schema::dropIfExists('photographer_works');
        });
    }
}
