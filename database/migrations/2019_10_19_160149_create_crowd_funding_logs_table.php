<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateCrowdFundingLogsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('crowd_funding_logs', function (Blueprint $table) {
            $table->engine = 'innodb';
            $table->increments('id');
            $table->unsignedInteger('user_id')->default(0)->comment('用户ID');
            $table->unsignedInteger("crowd_status")->default(0)->comment("是否参与众筹 0未参与,1参与");
            $table->unsignedInteger("order_id")->default(0)->nullable()->comment("众筹支付订单号");
            $table->timestamps();
        });
        DB::statement("ALTER TABLE `crowd_funding_logs` COMMENT '众筹记录表'");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('crowd_funding_logs');
    }
}
