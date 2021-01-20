<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateTableInviteSettings extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('invite_settings', function (Blueprint $table) {
            $table->engine = 'innodb';
            $table->increments('id');
            $table->dateTime('expiretime')->comment('邀请过期时间');
            $table->integer('times')->default(0)->comment('默认邀请次数');
            $table->text('cloudmedal')->nullable(false)->comment('勋章');
            $table->timestamps();
        });

        DB::statement("ALTER TABLE `invite_settings` COMMENT '前台：邀请设置表'"); // 表注释s
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('invite_settings');
    }
}
