<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterTableInviteRewardsAddColumns extends Migration
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
            $table->decimal("money")->default(0.00)->comment("当前拥有的金币");
            $table->decimal("money_count")->default(0.00)->comment("总共拥有的金币");
            $table->decimal("withdrawal_money")->default(0.00)->comment("要提现的金币");
            $table->decimal("withdrawal_money_count")->default(0.00)->comment("总共提现的金币");
            $table->decimal("withdrawal_cloud")->default(0.00)->comment("要提现的云朵");
            $table->decimal("withdrawal_cloud_count")->default(0.00)->comment("总共提现的云朵");
            $table->tinyInteger("is_withdrawal")->default(0)->comment("是否要提现");
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
