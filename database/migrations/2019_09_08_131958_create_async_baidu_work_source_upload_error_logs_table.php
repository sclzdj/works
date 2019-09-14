<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateAsyncBaiduWorkSourceUploadErrorLogsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create(
            'async_baidu_work_source_upload_error_logs',
            function (Blueprint $table) {
                $table->engine = 'innodb';
                $table->increments('id');
                $table->unsignedInteger('async_baidu_work_source_upload_id')->default(0)->comment('异步百度作品上传ID');
                $table->text('error_info')->nullable()->comment('错误信息');
                $table->timestamps();
            }
        );
        DB::statement("ALTER TABLE `async_baidu_work_source_upload_error_logs` COMMENT '前台：异步百度作品上传错误日志'"); // 表注释
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('async_baidu_work_source_upload_error_logs');
    }
}
