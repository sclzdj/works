<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreatePhotographerGathersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('photographer_gathers', function (Blueprint $table) {
            $table->engine = 'innodb';
            $table->increments('id');
            $table->unsignedInteger('photographer_id')->index()->default(0)->comment('用户ID');
            $table->unsignedInteger('photographer_gather_info_id')->index()->default(0)->comment('用户合集资料ID');
            $table->string('name',100)->default('')->comment('合集名称');
            $table->string('xacode',1000)->default('')->comment('项目小程序码');
            $table->string('xacode_hyaline',1000)->default('')->comment('透明合集小程码');
            $table->unsignedSmallInteger('status')->default(0)->comment('状态【0:草稿;200:成功;400:删除;500:失败】');
            $table->timestamps();
        });
        DB::statement("ALTER TABLE `photographer_gathers` COMMENT '前台：用户合集'"); // 表注释
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('photographer_gathers');
    }
}
