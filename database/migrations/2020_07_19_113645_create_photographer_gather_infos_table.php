<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreatePhotographerGatherInfosTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('photographer_gather_infos', function (Blueprint $table) {
            $table->engine = 'innodb';
            $table->increments('id');
            $table->unsignedInteger('photographer_id')->index()->default(0)->comment('用户ID');
            $table->unsignedInteger('photographer_rank_id')->index()->default(0)->comment('用户头衔ID');
            $table->string('start_year',50)->default('')->comment('起始年份');
            $table->unsignedTinyInteger('is_default')->default(0)->comment('是否默认');
            $table->unsignedSmallInteger('status')->default(0)->comment('状态【0:草稿;200:成功;400:删除;500:失败】');
            $table->timestamps();
        });
        DB::statement("ALTER TABLE `photographer_gather_infos` COMMENT '前台：用户合集资料库'"); // 表注释
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('photographer_gather_infos');
    }
}
