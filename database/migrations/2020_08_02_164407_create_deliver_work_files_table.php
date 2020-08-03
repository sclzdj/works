<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateDeliverWorkFilesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('deliver_work_files', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedBigInteger('work_id')->comment('作品ID');
            $table->string('dir', 1024)->default('/')->comment('文件夹路径，默认为/');
            $table->string('name', 512)->comment('文件名');
            $table->string('extension', 32)->default('')->comment('文件扩展名');
            $table->string('etag')->default('')->comment('云存储的文件唯一标识');
            $table->string('object_key', 512)->comment('云存储的文件名');
            $table->unsignedTinyInteger('file_type')->default(1)->comment('文件类型 0 未知, 1 图片, 2 视频');
            $table->unsignedInteger('size')->default(0)->comment('文件大小，单位为字节（Byte）');
            $table->unsignedInteger('pic_width')->default(0)->comment('图片宽度，单位像素');
            $table->unsignedInteger('pic_height')->default(0)->comment('图片高度，单位像素');
            $table->unsignedInteger('media_duration')->default(0)->comment('音视频时长，单位秒');
            $table->unsignedTinyInteger('is_dir')->default(0)->comment('是否是文件夹 0 否, 1 是');
            $table->unsignedTinyInteger('is_choice')->default(0)->comment('是否是精选 0 否, 1 是');
            $table->dateTime('deleted_at')->nullable();
            $table->timestamps();
            $table->index(['work_id', 'is_choice']);
        });

        //添加索引
        DB::statement("ALTER TABLE `deliver_work_files` ADD INDEX `idx_workid_dir` (`work_id`, `dir`(191))");
        //表注释
        DB::statement("ALTER TABLE `deliver_work_files` COMMENT '交付助手-作品文件表'");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('deliver_work_files');
    }
}
