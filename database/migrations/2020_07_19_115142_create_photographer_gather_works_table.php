<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreatePhotographerGatherWorksTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('photographer_gather_works', function (Blueprint $table) {
            $table->engine = 'innodb';
            $table->increments('id');
            $table->unsignedInteger('photographer_gather_id')->index()->default(0)->comment('用户合集ID');
            $table->unsignedInteger('photographer_work_id')->index()->default(0)->comment('用户项目ID');
            $table->unsignedInteger('sort')->default(0)->comment('排序');
        });
        DB::statement("ALTER TABLE `photographer_gather_works` COMMENT '前台：用户合集中的项目'"); // 表注释
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('photographer_gather_works');
    }
}
