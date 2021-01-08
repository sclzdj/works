<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterTableWithdrwalRecordsDropColumns extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        //
        Schema::table('withdrwal_records', function (Blueprint $table) {
            $table->dropColumn(['cloud']);
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
        Schema::table('withdrwal_records', function (Blueprint $table) {
            //
            Schema::dropIfExists('withdrwal_records');
        });
    }
}
