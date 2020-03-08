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
            $table->string('name', 50)->index()->default('')->comment('姓名');
            $table->unsignedTinyInteger('gender')->default(0)->comment('性别【0:未知;1:男;2:女】');
            $table->string('avatar', 1000)->default('')->comment('头像');
            $table->string('bg_img', 1000)->default('')->comment('背景');
            $table->unsignedInteger('province')->index()->default(0)->comment('省份');
            $table->unsignedInteger('city')->index()->default(0)->comment('城市');
            $table->unsignedInteger('area')->index()->default(0)->comment('地方');
            $table->unsignedInteger('photographer_rank_id')->default(0)->comment('头衔id');
            $table->string('wechat', 50)->default('')->comment('微信号');
            $table->string('mobile', 20)->unique()->nullable()->comment('手机号');
            $table->unsignedSmallInteger('status')->default(0)->comment('状态【0:草稿;200:成功;400:删除;500:失败】');
            $table->string('xacode',1000)->default('')->comment('作品集小程序码');
            $table->string('xacode_hyaline',1000)->default('')->comment('透明作品集小程码');
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
