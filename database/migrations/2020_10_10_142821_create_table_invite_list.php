<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateTableInviteList extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('invite_list', function (Blueprint $table) {
            $table->engine = 'innodb';
            $table->increments('id');
            $table->integer('parent_photographer_id')->comment('邀请者摄影师id');
            $table->integer('photographer_id')->nullable(false)->comment('受邀者摄影师id');
            $table->timestamps();
        });
        DB::statement("ALTER TABLE `invite_list` COMMENT '前台：邀请人归属表'"); // 表注释s
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('invite_list');
    }
}
