<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateTargetUsersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('target_users', function (Blueprint $table) {
            $table->increments('id');
            $table->integer("source")->comment("来源 0活动 1主页");
            $table->integer("status")->default(0)->comment("0未处理1已驳回2已通过3已发送4已创建");
            $table->integer("invote_code_id")->default(0)->comment("关联的邀请码");
            $table->integer("user_id")->default(0)->comment("用户id");
            $table->string('wechat' , 255)->default("")->comment("微信号");
            $table->text("address")->default("")->comment("地址");
            $table->text("phone_code")->comment("手机验证码");
            $table->text("works_info")->comment("作品信息");
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('target_users');
    }
}
