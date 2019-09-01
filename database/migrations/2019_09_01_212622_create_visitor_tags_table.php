<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateVisitorTagsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('visitor_tags', function (Blueprint $table) {
            $table->engine = 'innodb';
            $table->increments('id');
            $table->string('name', 100)->default('')->comment('名称');
            $table->unsignedInteger('sort')->default(0)->comment('排序');
        });
        DB::statement("ALTER TABLE `visitor_tags` COMMENT '前台：访客标签'"); // 表注释
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('visitor_tags');
    }
}
