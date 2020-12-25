<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateTableOrderInfo extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('order_info', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('pay_id')->nullable(false)->comment('users中的id');
            $table->decimal('money')->default(0.00)->comment('付费的金额');
            $table->string('pay_no')->nullable(false)->comment('订单号');
            $table->string('param')->nullable(true)->comment('额外参数');
            $table->integer('status')->default(0)->comment('是否支付');
            $table->dateTime('pay_time')->nullable(true)->comment('支付时间');
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
        Schema::dropIfExists('order_info');
    }
}
