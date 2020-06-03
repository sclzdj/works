<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddColumnToTargeUsers extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('target_users', function (Blueprint $table) {
            $table->string("reason" ,255)->default("")->comment("申请理由");
            $table->integer("rank_id" )->default(0)->comment("头衔");
            $table->string("last_name" ,255)->default("")->comment("用户姓名");
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('target_users', function (Blueprint $table) {
            $table->dropColumn(["reason", 'rank_id' , 'last_name']);
        });
    }
}
