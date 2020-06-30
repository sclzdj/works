<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateRecodeSceneTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('recode_scene', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('user_id')->comment("用户提交的id");
            $table->string("scene" , 255)->comment("用户提交的场景值");
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
        Schema::dropIfExists('recode_scene');
    }
}
