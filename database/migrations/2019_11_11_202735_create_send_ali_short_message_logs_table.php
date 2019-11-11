<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateSendAliShortMessageLogsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('send_ali_short_message_logs', function (Blueprint $table) {
            $table->engine = 'MyISAM';
            $table->increments('id');
            $table->string('mobile', 20)->index()->default('')->comment('手机号');
            $table->string('template_code', 20)->default('')->comment('模板code');
            $table->text('content_vars')->nullable()->comment('内容变量json变量');
            $table->unsignedSmallInteger('status')->default(0)->comment('状态【0:未知;200:成功;500:失败】');
            $table->text('third_response')->nullable()->comment('第三方返回的json');
            $table->timestamps();
        });
        DB::statement("ALTER TABLE `send_ali_short_message_logs` COMMENT '前台：发送阿里云短信记录'"); // 表注释
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('send_ali_short_message_logs');
    }
}
