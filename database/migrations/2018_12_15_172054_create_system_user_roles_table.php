<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateSystemUserRolesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('system_user_roles', function (Blueprint $table) {
            $table->engine = 'innodb';
            $table->increments('id');
            $table->unsignedInteger('system_user_id')->default(0)
                ->comment('账号');
            $table->unsignedInteger('system_role_id')->default(0)
                ->comment('角色');
        });
        DB::statement("ALTER TABLE `system_user_roles` COMMENT '后台:账号角色所属'"); // 表注释
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('system_user_roles');
    }
}
