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
            $table->string('province', 50)->default('')->comment('省份');
            $table->string('city', 50)->default('')->comment('城市');
            $table->string('area', 50)->default('')->comment('地方');
            $table->string('rank', 50)->default('')->comment('头衔');
            $table->string('wechat', 50)->default('')->comment('微信号');
            $table->string('mobile', 20)->unique()->default('')->comment('手机号');
            $table->timestamps();
        });
        DB::statement("ALTER TABLE `users` COMMENT '前台：摄影师'"); // 表注释
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
