<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateTablePhotographerGathersFilterRecord extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('photographer_gathers_filter_record', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('photographer_id')->default(0)->comment("摄影师id");
            $table->integer('photographer_gather_id')->default(0)->comment("合集ID");
            $table->text('category_ids')->nullable(true)->comment("作品类别信息");
            $table->text('customer_industries')->nullable(true)->comment("行业信息");
            $table->text('project_amount')->nullable(true)->comment("项目金额");
            $table->text('sheets_number')->nullable(true)->comment("拍摄张数");
            $table->text('shooting_duration')->nullable(true)->comment("拍摄时间");
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
        Schema::table('photographer_gathers_filter_record', function (Blueprint $table) {
            //
            Schema::dropIfExists('photographer_gathers_filter_record');
        });
    }
}
