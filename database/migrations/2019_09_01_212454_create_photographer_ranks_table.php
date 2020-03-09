<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreatePhotographerRanksTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create(
            'photographer_ranks',
            function (Blueprint $table) {
                $table->engine = 'innodb';
                $table->increments('id');
                $table->unsignedInteger('pid')->default(0)->comment('父ID');
                $table->unsignedTinyInteger('level')->default(0)->comment('级数');
                $table->string('name', 100)->default('')->comment('名称');
                $table->unsignedInteger('sort')->default(0)->comment('排序');
            }
        );
        DB::statement("ALTER TABLE `photographer_ranks` COMMENT '前台：用户头衔'"); // 表注释
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('photographer_ranks');
    }
}
