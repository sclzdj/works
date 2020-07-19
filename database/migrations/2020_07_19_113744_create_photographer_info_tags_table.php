<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreatePhotographerInfoTagsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('photographer_info_tags', function (Blueprint $table) {
            $table->engine = 'innodb';
            $table->increments('id');
            $table->unsignedInteger('photographer_id')->index()->default(0)->comment('用户ID');
            $table->unsignedInteger('photographer_gather_info_id')->index()->default(0)->comment('摄影师合集资料ID');
            $table->string('type',50)->default('')->comment('标签类型【auth:认证情况，award:获奖情况，educate:教育情况，equipment:器材清单,social:社交网络，brand:品牌】');
            $table->string('name',50)->default('')->comment('标签名');
        });
        DB::statement("ALTER TABLE `photographer_info_tags` COMMENT '前台：用户资料标签库'"); // 表注释
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('photographer_info_tags');
    }
}
