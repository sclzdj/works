<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateTableInviteFavour extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('invite_favour', function (Blueprint $table) {
            $table->increments('id');
            $table->Integer("favour_photographer_id")->nullable(false)->comment("邀请人");
            $table->Integer("request_photographer_id")->nullable(false)->comment("请求邀请的摄影师id");
            $table->Integer("final_photographer_id")->nullable(false)->comment("请求邀请的摄影师id");
            $table->Integer("status")->default(0)->comment("是否被邀请");
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('invite_favour');
    }
}
