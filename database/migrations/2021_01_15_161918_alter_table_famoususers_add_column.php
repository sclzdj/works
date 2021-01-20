<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterTableFamoususersAddColumn extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        //
        Schema::table('famoususers', function (Blueprint $table) {
            $table->text("cover")->after('video')->comment("封面");

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
        Schema::table('famoususers', function (Blueprint $table) {
            //
            Schema::dropIfExists('famoususers');
        });
    }
}
