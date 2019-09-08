<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateAsyncBaiduWorkUploadsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create(
            'async_baidu_work_uploads',
            function (Blueprint $table) {
                $table->engine = 'innodb';
                $table->increments('id');
                $table->unsignedInteger('user_id')->default(0)->comment('用户ID');
                $table->unsignedInteger('photographer_work_id')->default(0)->comment('作品集ID');
                $table->text('dlink')->nullable()->comment('百度网盘下载地址');
                $table->unsignedTinyInteger('category')->default(0)->comment('分类【1:视频;3:图片】');
                $table->unsignedBigInteger('size')->default(0)->comment('大小，单位B');
                $table->unsignedInteger('sort')->default(0)->comment('排序');
                $table->unsignedSmallInteger('status')->default(0)->comment('状态【0:等待中;200:成功;400:删除;500:失败】');
                $table->timestamps();
            }
        );
        DB::statement("ALTER TABLE `async_baidu_work_uploads` COMMENT '前台：异步百度作品上传'"); // 表注释
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('async_baidu_work_uploads');
    }
}
