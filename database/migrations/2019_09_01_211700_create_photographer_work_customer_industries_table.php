<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreatePhotographerWorkCustomerIndustriesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create(
            'photographer_work_customer_industries',
            function (Blueprint $table) {
                $table->engine = 'innodb';
                $table->increments('id');
                $table->unsignedInteger('pid')->default(0)->comment('父ID');
                $table->unsignedTinyInteger('level')->default(0)->comment('级数');
                $table->string('name', 100)->default('')->comment('名称');
                $table->unsignedInteger('sort')->default(0)->comment('排序');
            }
        );
        DB::statement("ALTER TABLE `photographer_work_customer_industries` COMMENT '前台：用户项目客户行业'"); // 表注释
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('photographer_work_customer_industries');
    }
}
