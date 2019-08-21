<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateSystemAreasTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('system_areas', function (Blueprint $table) {
            $table->engine = 'innodb';
            $table->unsignedInteger('id')->default(0)->comment('ID');
            $table->string('name')->index()->default('')->comment('名称');
            $table->unsignedInteger('pid')->default(0)->comment('父级ID');
            $table->string('short_name')->index()->default('')->comment('简称');
            $table->unsignedTinyInteger('level')->default(1)->comment('级别');
            $table->unsignedInteger('sort')->default(0)->comment('排序');
            $table->string('position')->default('')->comment('定位');
            $table->string('lng')->default('')->comment('经度');
            $table->string('lat')->default('')->comment('纬度');
        });
        DB::statement("ALTER TABLE `system_areas` COMMENT '后台:系统地区'"); // 表注释
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('system_areas');
    }
}
