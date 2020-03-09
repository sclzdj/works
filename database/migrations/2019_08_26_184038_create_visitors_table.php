<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateVisitorsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create(
            'visitors',
            function (Blueprint $table) {
                $table->engine = 'innodb';
                $table->increments('id');
                $table->unsignedInteger('photographer_id')->index()->default(0)->comment('用户ID');
                $table->unsignedInteger('user_id')->index()->default(0)->comment('访客的用户ID');
                $table->unsignedTinyInteger('is_remind')->default(0)->comment('是否提醒【0:否;1:是】');
                $table->unsignedInteger('visitor_tag_id')->default(0)->comment('标签id');
                $table->timestamp('last_operate_record_at')->nullable()->comment('最后操作记录时间');
                $table->timestamps();
                $table->unique(['photographer_id', 'user_id']);
            }
        );
        DB::statement("ALTER TABLE `view_records` COMMENT '前台：访客'"); // 表注释
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('visitors');
    }
}
