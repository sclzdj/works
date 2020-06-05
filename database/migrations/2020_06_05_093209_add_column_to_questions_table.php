<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddColumnToQuestionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {

        Schema::table('question', function (Blueprint $table) {
            $table->string("mobile_version", 255)->default("")->comment("手机型号");
            $table->string("system_version", 255)->default("")->comment("系统版本");
            $table->string("wechat_version", 255)->default("")->comment("微信版本");
            $table->string("language", 255)->default("")->comment("语言");
        });

    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('question', function (Blueprint $table) {
            $table->dropColumn(["mobile_version", 'system_version', 'wechat_version', 'language']);
        });
    }
}
