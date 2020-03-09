<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateQiniuPfopRichSourceJobsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('qiniu_pfop_rich_source_jobs', function (Blueprint $table) {
            $table->engine = 'innodb';
            $table->increments('id');
            $table->unsignedInteger('photographer_work_source_id')->index('job_source_id_index')->default(0)->comment('用户项目ID');
            $table->string('edit_node')->default('')->comment('修改节点');
            $table->timestamp('edit_at')->nullable()->comment('修改时间');
            $table->unsignedSmallInteger('status')->default(0)->comment('状态【0:等待中;1:执行中;200:成功;500:失败】');
            $table->timestamps();
        });
        DB::statement("ALTER TABLE `qiniu_pfop_rich_source_jobs` COMMENT '前台：七牛持久化水印任务表'"); // 表注释
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('qiniu_pfop_rich_source_jobs');
    }
}
