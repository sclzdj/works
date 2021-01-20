<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterTableWithdrwalRecordsAddColumns extends Migration
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
            $table->string('order_no')->nullable(false)->comment('提现订单号');
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
