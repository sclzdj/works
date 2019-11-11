<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateOperateRecordsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('operate_records', function (Blueprint $table) {
            $table->engine = 'innodb';
            $table->increments('id');
            $table->unsignedInteger('user_id')->index()->default(0)->comment('用户ID');
            $table->unsignedInteger('photographer_id')->index()->default(0)->comment('摄影师的ID');
            $table->unsignedInteger('photographer_work_id')->default(0)->comment('摄影师作品集的ID');
            $table->string('page_name',100)->default('')->comment('页面名称');
            $table->string('operate_type',100)->default('')->comment('操作类型');
            $table->string('in_type',100)->default('')->comment('进入方式');
            $table->string('share_type',100)->default('')->comment('分享方式');
            $table->unsignedInteger('shared_user_id')->default(0)->comment('分享人的用户ID');
            $table->unsignedTinyInteger('is_read')->default(0)->comment('是否已读');
            $table->timestamps();
            $table->index(['photographer_id', 'user_id']);
        });
        DB::statement("ALTER TABLE `operate_records` COMMENT '前台：操作记录'"); // 表注释
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('operate_records');
    }
}
