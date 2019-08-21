<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateSystemConfigsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('system_configs', function (Blueprint $table) {
            $table->engine = 'innodb';
            $table->increments('id');
            $table->string('name')->unique()->default('')->comment('标识');
            $table->string('title')->index()->default('')->comment('名称');
            $table->longText('value')->comment('值');
            $table->string('type')->default('basic')->comment('分类');
            $table->string('genre')->default('text')->comment('输入样式');
            $table->string('tips')->default('')->comment('提示信息');
            $table->text('options')->comment('选项值，必需是一个单维的json格式');
            $table->unsignedTinyInteger('required')->default(0)
                ->comment('是否必需');
        });
        DB::statement("ALTER TABLE `system_configs` COMMENT '后台:系统配置'"); // 表注释
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('system_configs');
    }
}
