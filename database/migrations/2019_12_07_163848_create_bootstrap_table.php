<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateBootstrapTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('bootstraps', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('user_id')->default(0)->comment('用户ID');
            $table->unsignedInteger('home')->default(0)->comment('主页');
            $table->unsignedInteger('work')->default(0)->comment('作品');
            $table->unsignedInteger('user')->default(0)->comment('用户控制台');
            $table->unsignedInteger('relation')->default(0)->comment('人脉');
            $table->unsignedInteger('storage')->default(0)->comment('图库');
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
        Schema::dropIfExists('bootstraps');
    }
}
