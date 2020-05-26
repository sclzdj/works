<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddColumnToInvoteTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('invote_codes', function (Blueprint $table) {
            $table->integer("used_count" )->default(1)->comment("验证码使用次数");
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('invote_codes', function (Blueprint $table) {
            $table->dropColumn("used_count");
        });
    }
}
