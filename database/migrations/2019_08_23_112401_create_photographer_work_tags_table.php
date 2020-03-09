<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreatePhotographerWorkTagsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('photographer_work_tags', function (Blueprint $table) {
            $table->engine = 'innodb';
            $table->increments('id');
            $table->unsignedInteger('photographer_work_id')->index()->default(0)->comment('用户项目ID');
            $table->string('name',50)->default('')->comment('标签名');
        });
        DB::statement("ALTER TABLE `photographer_work_tags` COMMENT '前台：用户项目标签'"); // 表注释
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('photographer_work_tags');
    }
}
