<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateSmsCodesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('sms_codes', function (Blueprint $table) {
            $table->engine = 'innodb';
            $table->increments('id');
            $table->string('mobile', 20)->default('')->comment('手机号');
            $table->string('code', 20)->default('')->comment('短信验证码');
            $table->string('purpose',50)->default('')->comment('用途');
            $table->string('ip',50)->default('')->comment('IP');
            $table->unsignedTinyInteger('is_used')->default(0)->comment('是否使用【0:否;1:是】');
            $table->string('third_type',50)->default('')->comment('第三方类型');
            $table->text('third_response')->nullable()->comment('第三方成功返回的json');
            $table->timestamp('expired_at')->nullable()->comment('过期时间');
            $table->timestamps();
        });
        DB::statement("ALTER TABLE `sms_codes` COMMENT '前台：短信验证码'"); // 表注释
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('sms_codes');
    }
}
