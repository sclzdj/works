<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddColumnToQuestionUserTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('question_user', function (Blueprint $table) {
            $table->string("user_mobile_version", 255)->default("")->nullable()->comment("手机型号");
            $table->string("user_system_version", 255)->default("")->nullable()->comment("系统版本");
            $table->string("user_wechat_version", 255)->default("")->nullable()->comment("微信版本");
            $table->string("user_language", 255)->default("")->nullable()->comment("语言");
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('question_user', function (Blueprint $table) {
            //
        });
    }
}
