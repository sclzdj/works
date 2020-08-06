<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateDeliverWorkSyncPanJobsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('deliver_work_sync_pan_jobs', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedBigInteger('work_id')->comment('作品ID');
            $table->unsignedBigInteger('obtain_id')->comment('提取ID');
            $table->string('dir', 1024)->comment('作品存储在网盘的文件夹路径');
            $table->string('access_token', 128)->comment('百度授权的access_token');
            $table->string('fail_reason', 512)->default('')->comment('同步失败原因');
            $table->unsignedTinyInteger('operator_type')->default(1)->comment('任务发起人类型 1 提取人, 2 作者');
            $table->unsignedTinyInteger('status')->default(0)->comment('同步状态 0 等待队列被拉取, 1 处理中, 2 成功, 3 失败');
            $table->timestamps();
            $table->index('work_id');
            $table->index('obtain_id');
        });

        //表注释
        DB::statement("ALTER TABLE `deliver_work_sync_pan_jobs` COMMENT '交付助手-作品同步百度网盘任务表'");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('deliver_work_sync_pan_jobs');
    }
}
