<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterTablePhotographerWorksModifyColumns extends Migration
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
            $table->integer("sheets_number")->nullable(true)->comment("成片张数")->change();
            $table->integer("shooting_duration")->nullable(true)->comment("拍摄时长，小时")->change();
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
        Schema::table('invite_rewards', function (Blueprint $table) {
            //
            Schema::dropIfExists('invite_rewards');
        });
    }
}
