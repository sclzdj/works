<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateTableInviteReward extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('invite_rewards', function (Blueprint $table) {
            $table->increments('id');
            $table->Integer("photographer_id")->nullable(false)->comment("摄影师id");
            $table->decimal("cloud")->default(0.00)->comment("当前拥有的云朵");
            $table->decimal("cloud_count")->default(0.00)->comment("总共拥有的云朵");
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
        Schema::dropIfExists('invite_rewards');
    }
}
