<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterTablePhotographersAddColumn extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        //
        Schema::table('photographers', function (Blueprint $table) {
            $table->string("share_xacode")->nullable(true)->comment("裂变二维码");
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        //
        Schema::table('photographers', function (Blueprint $table) {
            //
            Schema::dropIfExists('invite_rewards');
        });
    }
}
