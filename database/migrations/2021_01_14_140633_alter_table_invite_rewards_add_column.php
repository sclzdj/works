<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterTableInviteRewardsAddColumn extends Migration
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
            $table->decimal("withdrawal_money")->default(0.00)->comment("提现中的钱");

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
