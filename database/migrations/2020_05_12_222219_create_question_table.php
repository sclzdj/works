<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateQuestionTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('question', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('status')->default(0)->comment("状态 0待沟通1待认领2处理中3已处理4已归档5被合并6被搁置");
            $table->integer('type')->default(0)->comment("类型 1bug 2建议");
            $table->string("page" , 255)->comment("页面");
            $table->string("content" , 255)->comment("反馈的问题");
            $table->text("attachment")->comment("附件的图片")->nullable();
            $table->unsignedInteger('user_id')->default(0)->comment('用户id');
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
        Schema::dropIfExists('question');
    }
}
