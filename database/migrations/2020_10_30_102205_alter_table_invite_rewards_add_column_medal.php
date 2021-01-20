<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterTableInviteRewardsAddColumnMedal extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        //
        Schema::table('invite_rewards', function (Blueprint $table) {
            $table->string("medal")->nullable(true)->comment("勋章等级");
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
        Schema::table('invite_rewards', function (Blueprint $table) {
            //
            Schema::dropIfExists('invite_rewards');
        });
    }
}
