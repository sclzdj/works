<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateTableFamoususer extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('famoususers', function (Blueprint $table) {
            $table->engine = 'innodb';
            $table->increments('id');
            $table->integer('photographer_id')->comment('摄影师id');
            $table->integer('status')->default(0)->comment('状态');
            $table->timestamps();
        });
        DB::statement("ALTER TABLE `famoususers` COMMENT '前台：大咖表'");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('famoususers');
    }
}
