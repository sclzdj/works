<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddReviewToPhotographerWorkSources extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('photographer_work_sources', function (Blueprint $table) {
            //
            $table->integer('review')->after('status')->default(0)->comment("审核信息");
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('photographer_work_sources', function (Blueprint $table) {
            //
            Schema::dropIfExists('photographer_work_sources');
        });
    }
}
