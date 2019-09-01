<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreatePhotographersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('photographers', function (Blueprint $table) {
            $table->engine = 'innodb';
            $table->increments('id');
            $table->string('name', 50)->default('')->comment('姓名');
            $table->string('avatar', 1000)->default('')->comment('头像');
            $table->string('bg_img', 1000)->default('')->comment('背景');
            $table->string('province', 50)->default('')->comment('省份');
            $table->string('city', 50)->default('')->comment('城市');
            $table->string('area', 50)->default('')->comment('地方');
            $table->string('photographer_rank_id', 50)->default('')->comment('头衔id');
            $table->string('wechat', 50)->default('')->comment('微信号');
            $table->string('mobile', 20)->index()->default('')->comment('手机号');
            $table->unsignedSmallInteger('status')->default(0)->comment('状态【0:草稿;200:成功;500:失败】');
            $table->timestamps();
        });
        DB::statement("ALTER TABLE `photographers` COMMENT '前台：摄影师'"); // 表注释
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('photographers');
    }
}
