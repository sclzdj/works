<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterTableInviteRewardsDropColumns extends Migration
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
            $table->dropColumn(['cloud', 'cloud_count', 'withdrawal_cloud_count']);
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
