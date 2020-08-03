<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateDeliveryWorkObtainsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('delivery_work_obtains', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedBigInteger('work_id')->comment('作品ID');
            $table->string('phone', 32)->default('')->comment('提取人手机号');
            $table->string('code', 32)->comment('提取码');
            $table->unsignedTinyInteger('is_sync_pan')->default(0)->comment('是否同步到百度网盘 0 未同步, 1 同步');
            $table->unsignedTinyInteger('is_download')->default(0)->comment('是否下载 0 未下载, 1 已下载');
            $table->unsignedTinyInteger('status')->default(0)->comment('提取状态 1 未提取, 1 已提取');
            $table->dateTime('expired_at')->comment('失效时间');
            $table->timestamps();
            $table->index('work_id');
        });

        //表注释
        DB::statement("ALTER TABLE `delivery_work_obtains` COMMENT '交付助手-作品提取表'");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('delivery_work_obtains');
    }
}
