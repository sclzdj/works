<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateSystemRoleNodesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('system_role_nodes', function (Blueprint $table) {
            $table->engine = 'innodb';
            $table->increments('id');
            $table->unsignedInteger('system_role_id')->index()->default(0)
                ->comment('角色');
            $table->unsignedInteger('system_node_id')->index()->default(0)
                ->comment('节点');
        });
        DB::statement("ALTER TABLE `system_role_nodes` COMMENT '后台:角色节点分配'"); // 表注释
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('system_role_nodes');
    }
}
