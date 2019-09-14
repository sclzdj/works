<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateAsyncBaiduWorkSourcesUploadsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create(
            'async_baidu_work_sources_uploads',
            function (Blueprint $table) {
                $table->engine = 'innodb';
                $table->increments('id');
                $table->unsignedInteger('user_id')->default(0)->comment('用户ID');
                $table->unsignedInteger('photographer_work_id')->default(0)->comment('作品集ID');
                $table->unsignedSmallInteger('status')->default(0)->comment('状态【0:等待中;200:成功;400:删除;500:失败】');
                $table->timestamps();
            }
        );
        DB::statement("ALTER TABLE `async_baidu_work_sources_uploads` COMMENT '前台：异步百度作品上传'"); // 表注释
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('async_baidu_work_sources_uploads');
    }
}
