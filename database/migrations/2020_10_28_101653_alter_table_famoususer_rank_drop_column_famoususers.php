<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterTableFamoususerRankDropColumnFamoususers extends Migration
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
            $table->dropColumn('famoususer_id');
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
