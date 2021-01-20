<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterTablePhotographersAddColumns extends Migration
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
            $table->tinyInteger("famoususer_id")->default(0)->comment("大咖ID");
            $table->dropColumn('is_famous');
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
            Schema::dropIfExists('photographers');
        });
    }
}
