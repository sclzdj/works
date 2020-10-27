<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateTableFamoususerRank extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('famoususer_rank', function (Blueprint $table) {
            $table->engine = 'innodb';
            $table->increments('id');
            $table->integer('famoususer_id')->comment('大咖id');
            $table->integer('photographer_rank_id')->nullable(false)->comment('摄影师领域');
            $table->timestamps();
        });
        DB::statement("ALTER TABLE `famoususer_rank` COMMENT '前台：大咖标签表'");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('famoususer_rank');
    }
}
