<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreatePhotographerWorkImgsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('photographer_work_imgs', function (Blueprint $table) {
            $table->engine = 'innodb';
            $table->increments('id');
            $table->unsignedInteger('photographer_work_id')->default(0)->comment('摄影师作品集ID');
            $table->string('img_url')->default('')->comment('图片地址');
            $table->unsignedInteger('sort')->default(0)->comment('排序');
            $table->timestamps();
        });
        DB::statement("ALTER TABLE `photographer_work_imgs` COMMENT '前台：摄影师作品集图片'"); // 表注释
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('photographer_work_imgs');
    }
}
