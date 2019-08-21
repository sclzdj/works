<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateSystemRolesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('system_roles', function (Blueprint $table) {
            $table->engine = 'innodb';
            $table->increments('id');
            $table->string('name')->unique()->default('')->comment('名称');
            $table->unsignedTinyInteger('status')->default(1)
                ->comment('状态:0=>禁用 1=>启用');
            $table->timestamps();
        });
        DB::statement("ALTER TABLE `system_roles` COMMENT '后台:系统角色'"); // 表注释
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('system_roles');
    }
}
