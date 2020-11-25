<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterTablePhotographerGatherInfoAddColumn extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        //
        Schema::table('photographer_gather_infos', function (Blueprint $table) {
            $table->integer("sort")->after('status')->default(1)->comment("排序");
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
        Schema::table('photographer_gather_infos', function (Blueprint $table) {
            //
            Schema::dropIfExists('photographer_gather_infos');
        });
    }
}
