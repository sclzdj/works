<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateCrowdFundingOrdersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('crowd_funding_orders', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('user_id')->default(0)->comment('用户ID');
            $table->string("order_trade_no", 255)->default("")->comment("用户电话");
            $table->unsignedInteger("pay_status")->default(0)->comment("是否支付 0未支付,1支付");
            $table->unsignedInteger("type")->default(0)->comment("参与的众筹档0未参与,1 2 3个档位");
            $table->unsignedInteger("notify")->default(0)->comment("是否回调处理 0未处理,1处理完成");
            $table->string("transaction_id" , 255)->default("")->nullable()->comment("微信订单号");
            $table->decimal("price")->comment("付款金额");
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
        Schema::dropIfExists('crowd_funding_orders');
    }
}
