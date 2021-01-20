<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterTableInviteRewardsAddColumn extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        //
        Schema::table('invite_rewards', function (Blueprint $table) {
            $table->integer('invite_rank')->default(0)->comment('邀请资格排名');
            $table->dateTime('baicloud_time')->nullable(true)->comment('白云获得时间');
            $table->integer('baicloud_alert')->default(0)->comment('白云弹窗');
            $table->dateTime('qincloud_time')->nullable(true)->comment('轻云获得时间');
            $table->integer('qincloud_alert')->default(0)->comment('轻云弹窗');
            $table->dateTime('juancloud_time')->nullable(true)->comment('卷云获得时间');
            $table->integer('juancloud_alert')->default(0)->comment('卷云弹窗');
            $table->dateTime('jicloud_time')->nullable(true)->comment('积云获得时间');
            $table->integer('jicloud_alert')->default(0)->comment('积云弹窗');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('invite_rewards', function (Blueprint $table) {
            Schema::dropIfExists('invite_rewards');
        });
    }
}
