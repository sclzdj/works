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
                $table->unsignedInteger('photographer_id')->index()->default(0)->comment('用户ID');
                $table->unsignedInteger('ranking')->default(0)->comment('排名');
                $table->timestamps();
            }
        );
        DB::statement("ALTER TABLE `photographer_ranking_logs` COMMENT '前台：用户排名记录'"); // 表注释
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
