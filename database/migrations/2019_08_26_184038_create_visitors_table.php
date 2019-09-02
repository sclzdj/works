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
                $table->unsignedInteger('photographer_id')->default(0)->comment('摄影师ID');
                $table->unsignedInteger('user_id')->default(0)->comment('访客的用户ID');
                $table->unsignedTinyInteger('is_remind')->default(0)->comment('是否提醒【0:否;1:是】');
                $table->unsignedInteger('visitor_tag_id')->default(0)->comment('标签id');
                $table->unsignedInteger('unread_count')->default(0)->comment('未读数量');
                $table->timestamps();
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
