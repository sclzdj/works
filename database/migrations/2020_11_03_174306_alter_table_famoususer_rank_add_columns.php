<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterTableFamoususerRankAddColumns extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        //
        Schema::table('famoususer_rank', function (Blueprint $table) {
            $table->integer("sort")->default(0)->comment("排序");
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
        Schema::table('famoususer_rank', function (Blueprint $table) {
            //
            Schema::dropIfExists('famoususer_rank');
        });
    }
}
