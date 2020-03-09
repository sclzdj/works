<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateInvoteCodesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('invote_codes', function (Blueprint $table) {
            $table->engine = 'innodb';
            $table->increments('id');
            $table->string('code' , 6)->default("")->comment("邀请码");
            $table->unsignedInteger('type')->default(0)->comment("邀请码生成类型 1微信用户创建，2后台创建");
            $table->unsignedInteger('status')->default(0)->comment("状态 0未使用，1已占用 2已使用");
            $table->unsignedInteger('is_use')->default(0)->comment("是否使用过 0 未使用 1使用过");
            $table->unsignedInteger('is_send')->default(0)->comment("是否发送了邀请码 0 未发送 1发送了");
            $table->unsignedInteger('user_id')->index()->default(0)->comment('微信用户ID');
            $table->unsignedInteger('order_id')->default(0)->comment("通过订单创建的邀请码");
            $table->timestamps();
        });
        DB::statement("ALTER TABLE `invote_codes` COMMENT '邀请码表'");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('invote_codes');
    }
}
