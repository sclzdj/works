<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateQiniuPfopRichSourceJobLogsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('qiniu_pfop_rich_source_job_logs', function (Blueprint $table) {
            $table->engine = 'innodb';
            $table->increments('id');
            $table->unsignedInteger('photographer_work_source_id')->index('job_source_id_index')->default(0)->comment('用户项目ID');
            $table->string('edit_node')->default('')->comment('修改节点');
            $table->timestamp('edit_at')->nullable()->comment('修改时间');
            $table->string('rich_key',1000)->index()->default('')->comment('丰富展示的资源key');
            $table->string('rich_url',1000)->default('')->comment('丰富展示的资源地址');
            $table->text('qiniu_response')->nullable()->comment('第三方返回的json');
            $table->unsignedSmallInteger('status')->default(0)->comment('状态【0:等待中;1:执行中;200:成功;500:失败】');
            $table->timestamps();
        });
        DB::statement("ALTER TABLE `qiniu_pfop_rich_source_job_logs` COMMENT '前台：七牛持久化水印任务日志表'"); // 表注释
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('qiniu_pfop_rich_source_job_logs');
    }
}
