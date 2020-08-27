<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddColumnToPhotographerGatherInfos extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('photographer_gather_infos', function (Blueprint $table) {
            //
            $table->integer('showtype')->after('start_year')->default(1)->comment("合集项目展示类型");
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('photographer_gather_infos', function (Blueprint $table) {
            //
            Schema::dropIfExists('photographer_gather_infos');
        });
    }
}
