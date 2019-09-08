<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateBaiduOauthsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create(
            'baidu_oauths',
            function (Blueprint $table) {
                $table->engine = 'innodb';
                $table->increments('id');
                $table->unsignedInteger('user_id')->default(0)->comment('用户ID');
                $table->string('access_token', 1000)->default('')->comment('token');
                $table->timestamp('expired_at')->nullable()->comment('过期时间');
                $table->timestamps();
            }
        );
        DB::statement("ALTER TABLE `baidu_oauths` COMMENT '前台：百度授权'"); // 表注释
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('baidu_oauths');
    }
}
