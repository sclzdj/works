<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateSystemFilesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('system_files', function (Blueprint $table) {
            $table->engine = 'innodb';
            $table->increments('id');
            $table->string('name', 1000)->index()->default('')->comment('通用文件名');
            $table->string('url', 1000)->index()->default('')->comment('文件链接');
            $table->string('original_url', 1000)->index()->default('')->comment('原始文件链接');
            $table->string('filename', 1000)->index()->default('')->comment('文件名，不含后缀');
            $table->string('object', 1000)->index()->default('')->comment('文件对象名，含后缀');
            $table->string('objects', 4000)->default('')->comment('文件对象名集合，含后缀');
            $table->string('extension')->default('')->comment('后缀名');
            $table->string('mimeType')->default('')->comment('mime类型');
            $table->unsignedInteger('size')->default(0)->comment('大小');
            $table->string('disk')->default('')->comment('磁盘');
            $table->string('driver')->default('')->comment('驱动');
            $table->string('scene')->default('')->comment('场景');
            $table->string('upload_type')->default('')->comment('上传方式');
            $table->timestamps();
        });
        DB::statement("ALTER TABLE `system_files` COMMENT '后台:系统文件'"); // 表注释
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('system_files');
    }
}
