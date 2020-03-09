<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateViewRecordsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('view_records', function (Blueprint $table) {
            $table->engine = 'innodb';
            $table->increments('id');
            $table->unsignedInteger('user_id')->index()->default(0)->comment('用户ID');
            $table->unsignedInteger('photographer_id')->index()->default(0)->comment('访问的用户ID');
            $table->unsignedTinyInteger('is_newest')->default(0)->comment('是否最新【0:否;1:是】');
            $table->timestamps();
        });
        DB::statement("ALTER TABLE `view_records` COMMENT '前台：浏览记录'"); // 表注释
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('view_records');
    }
}
