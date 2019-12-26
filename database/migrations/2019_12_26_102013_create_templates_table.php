<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateTemplatesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('templates', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('number')->default(0)->comment('序号');
            $table->string("purpose", 255)->default("")->comment("用途");
            $table->string("text1", 255)->default("")->comment("文案1");
            $table->string("text2", 255)->default("")->comment("文案2");
            $table->string("text3", 255)->default("")->comment("文案3");
            $table->string("text4", 255)->default("")->comment("文案4");
            $table->string("background", 255)->default("")->comment("背景图");
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
        Schema::dropIfExists('templates');
    }
}
