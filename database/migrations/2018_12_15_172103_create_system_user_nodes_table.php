<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateSystemUserNodesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('system_user_nodes', function (Blueprint $table) {
            $table->engine = 'innodb';
            $table->increments('id');
            $table->unsignedInteger('system_user_id')->default(0)
                ->comment('账号');
            $table->unsignedInteger('system_node_id')->default(0)
                ->comment('节点');
        });
        DB::statement("ALTER TABLE `system_user_nodes` COMMENT '后台:账号节点直赋'"); // 表注释
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('system_user_nodes');
    }
}
