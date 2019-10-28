<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreatePhotographerRankingLogsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create(
            'photographer_ranking_logs',
            function (Blueprint $table) {
                $table->engine = 'innodb';
                $table->increments('id');
                $table->unsignedInteger('photographer_id')->default(0)->comment('摄影师ID');
                $table->unsignedInteger('ranking')->default(0)->comment('排名');
                $table->timestamps();
            }
        );
        DB::statement("ALTER TABLE `photographer_ranking_logs` COMMENT '前台：摄影师排名记录'"); // 表注释
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('photographer_ranking_logs');
    }
}
