<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddColumnToPhotographers extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('photographers', function (Blueprint $table) {
            //
            $table->integer('invite')->after('review')->default(0)->comment("邀请次数");
            $table->integer('is_setup')->after('is_upgrade')->default(0)->comment("是否创建");
            $table->integer('level')->after('is_setup')->default(0)->comment("用户等级");
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('photographers', function (Blueprint $table) {
            //
            Schema::dropIfExists('photographers');
        });
    }
}
