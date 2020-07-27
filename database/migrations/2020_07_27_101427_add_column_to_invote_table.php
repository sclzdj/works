<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddColumnToInvoteTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('invite', function (Blueprint $table) {
            $table->string("remark2")->default("")->nullable()->comment("运营");
            $table->string("remark3")->default("")->nullable()->comment("类别");
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('invite', function (Blueprint $table) {
            //
        });
    }
}
