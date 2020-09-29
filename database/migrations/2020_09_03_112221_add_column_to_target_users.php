<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddColumnToTargetUsers extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('target_users', function (Blueprint $table) {
            //
            $table->integer('pid')->after('id')->default(0)->comment("上级用户");
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
            //
            Schema::dropIfExists('target_users');
        });
    }
}
