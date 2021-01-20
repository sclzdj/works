<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateTableSources extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('sources', function (Blueprint $table) {
            $table->engine = 'innodb';
            $table->increments('id');
            $table->string('name',100)->default('')->comment('来源说明');
            $table->integer('sid')->default(0)->comment('序号');
            $table->timestamps();
        });
        DB::statement("ALTER TABLE `sources` COMMENT '后台: 用户来源'"); // 表注释s
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('sources');
    }
}
