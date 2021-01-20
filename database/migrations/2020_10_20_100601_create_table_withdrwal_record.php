<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateTableWithdrwalRecord extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('withdrwal_records', function (Blueprint $table) {
            $table->increments('id');
            $table->Integer("photographer_id")->nullable(false)->comment("摄影师id");
            $table->decimal("money")->default(0.00)->comment("提现的金额");
            $table->decimal("cloud")->default(0.00)->comment("提现的云朵");
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('withdrwal_records');
    }
}
