<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterTableBootstrapsAddColumn extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        //
        Schema::table('bootstraps', function (Blueprint $table) {
            $table->integer("preview")->default(0)->after('storage')->comment("预览大图");

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
        Schema::table('bootstraps', function (Blueprint $table) {
            //
            Schema::dropIfExists('bootstraps');
        });
    }
}
