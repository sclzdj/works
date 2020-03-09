<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateDocPdfsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('doc_pdfs', function (Blueprint $table) {
            $table->engine = 'innodb';
            $table->increments('id');
            $table->unsignedInteger('photographer_id')->index()->default(0)->comment('用户ID');
            $table->string('name', 100)->default('')->comment('名称');
            $table->unsignedInteger('estimate_completion_time')->default(0)->comment('预估完成时间,单位秒');
            $table->string('url')->default('')->comment('文档地址');
            $table->unsignedSmallInteger('status')->default(0)->comment('状态【0:等待中;200:成功;400:删除;500:失败】');
            $table->timestamps();
        });
        DB::statement("ALTER TABLE `doc_pdfs` COMMENT '前台：PDF文档'"); // 表注释
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('doc_pdfs');
    }
}
