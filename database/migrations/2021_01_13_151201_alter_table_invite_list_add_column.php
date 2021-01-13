<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterTableInviteListAddColumn extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        //
        Schema::table('invite_list', function (Blueprint $table) {
            $table->integer("is_read")->default(0)->comment("是否已读");

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
        Schema::table('invite_list', function (Blueprint $table) {
            //
            Schema::dropIfExists('invite_list');
        });
    }
}
