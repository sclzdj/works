<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterTablePhotographersAddColumnFamousId extends Migration
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
            $table->tinyInteger("is_famous")->default(0)->comment("是否为大咖");
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
