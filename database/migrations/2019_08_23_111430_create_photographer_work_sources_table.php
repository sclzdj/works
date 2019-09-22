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
            $table->string('key',1000)->default('')->comment('初始资源key');
            $table->string('url',1000)->default('')->comment('初始资源地址');
            $table->unsignedBigInteger('size')->default(0)->comment('初始资源大小B');
            $table->unsignedInteger('width')->default(0)->comment('初始资源宽,px');
            $table->unsignedInteger('height')->default(0)->comment('初始资源高,px');
            $table->string('deal_key',1000)->default('')->comment('处理后的资源key');
            $table->string('deal_url',1000)->default('')->comment('处理后的资源地址');
            $table->unsignedBigInteger('deal_size')->default(0)->comment('处理后的资源大小B');
            $table->unsignedInteger('deal_width')->default(0)->comment('处理后的资源宽,px');
            $table->unsignedInteger('deal_height')->default(0)->comment('处理后的资源高,px');
            $table->string('rich_key',1000)->default('')->comment('丰富展示的资源key');
            $table->string('rich_url',1000)->default('')->comment('丰富展示的资源地址');
            $table->unsignedBigInteger('rich_size')->default(0)->comment('丰富展示的资源大小B');
            $table->unsignedInteger('rich_width')->default(0)->comment('丰富展示的资源宽,px');
            $table->unsignedInteger('rich_height')->default(0)->comment('丰富展示的资源高,px');
            $table->string('type')->default('')->comment('资源类型');
            $table->string('origin')->default('')->comment('资源来源');
            $table->unsignedInteger('sort')->default(0)->comment('排序');
            $table->unsignedSmallInteger('status')->default(0)->comment('状态【0:草稿;200:成功;300:覆盖;400:删除;500:失败】');
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
