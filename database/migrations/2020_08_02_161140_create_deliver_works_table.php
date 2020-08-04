<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateDeliverWorksTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('deliver_works', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedBigInteger('user_id')->comment('用户ID');
            $table->unsignedBigInteger('photographer_id')->comment('摄影师ID');
            $table->unsignedInteger('category_id')->default(0)->comment('作品分类ID');
            $table->unsignedInteger('customer_industry_id')->default(0)->comment('客户行业ID');
            $table->string('name', 512)->comment('作品名称');
            $table->string('cover', 512)->default("")->comment('作品封面');
            $table->unsignedInteger('file_total_num')->comment('文件总大小，单位B');
            $table->unsignedInteger('file_total_size')->comment('文件总大小，单位B');
            $table->unsignedInteger('download_num')->comment('下载次数');
            $table->unsignedTinyInteger('is_download')->comment('客户是否已下载 0 未下载, 1 已下载（只要有一个提取人下载就认定已下载）');
            $table->dateTime('expired_at')->comment('失效时间');
            $table->dateTime('deleted_at')->nullable();
            $table->timestamps();
            $table->index('user_id');
            $table->index('photographer_id');
        });

        //表注释
        DB::statement("ALTER TABLE `deliver_works` COMMENT '交付助手-作品表'");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('deliver_works');
    }
}
