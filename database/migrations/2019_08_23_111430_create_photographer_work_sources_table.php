<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreatePhotographerWorkSourcesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('photographer_work_sources', function (Blueprint $table) {
            $table->engine = 'innodb';
            $table->increments('id');
            $table->unsignedInteger('photographer_work_id')->default(0)->comment('摄影师作品集ID');
            $table->string('url')->default('')->comment('资源地址');
            $table->unsignedInteger('init_size')->default(0)->comment('初始资源大小KB');
            $table->string('deal_url')->default('')->comment('处理后的资源地址');
            $table->unsignedInteger('deal_size')->default(0)->comment('处理后的资源大小KB');
            $table->string('type')->default('')->comment('资源类型');
            $table->string('origin')->default('')->comment('资源来源');
            $table->unsignedInteger('sort')->default(0)->comment('排序');
            $table->timestamps();
        });
        DB::statement("ALTER TABLE `photographer_work_sources` COMMENT '前台：摄影师作品集资源'"); // 表注释
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('photographer_work_sources');
    }
}
