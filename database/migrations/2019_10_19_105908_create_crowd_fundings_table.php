<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateCrowdFundingsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('crowd_fundings', function (Blueprint $table) {
            $table->engine = 'innodb';
            $table->increments('id');
            $table->decimal('amount', 8, 2)->comment('众筹总金额');
            $table->unsignedInteger('total')->default(0)->comment('众筹总人数');
            $table->unsignedInteger('total_price')->default(0)->comment('众筹金额');
            $table->unsignedInteger('target')->default(0)->comment('众筹目标值');
            $table->string('complete_rate', 100)->default(0)->comment('达成率');
            $table->unsignedInteger('limit_99')->default(0)->comment('99限制');
            $table->unsignedInteger('data_99')->default(0)->comment('99实购');
            $table->unsignedInteger('limit_399')->default(0)->comment('399限制');
            $table->unsignedInteger('data_399')->default(0)->comment('399实购');
            $table->unsignedInteger('limit_599')->default(0)->comment('599限制');
            $table->unsignedInteger('data_599')->default(0)->comment('599实购');
            $table->unsignedInteger('start_date')->default(0)->comment('众筹开始时间');
            $table->unsignedInteger('end_date')->default(0)->comment('众筹结束时间');
            $table->unsignedInteger('send_date')->default(0)->comment('推送时间');
            $table->timestamps();
        });
        DB::statement("ALTER TABLE `crowd_fundings` COMMENT '众筹数据'"); // 表注释
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('crowd_fundings');
    }
}
