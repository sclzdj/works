<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateHelpNotesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create(
            'help_notes',
            function (Blueprint $table) {
                $table->engine = 'innodb';
                $table->increments('id');
                $table->string('title', 1000)->default('')->comment('标题');
                $table->text('content')->comment('内容');
                $table->unsignedSmallInteger('status')->default(0)->comment('状态');
                $table->integer('sort')->default(0)->comment('排序');
                $table->timestamps();
            }
        );
        DB::statement("ALTER TABLE `help_notes` COMMENT '前台：帮助说明'"); // 表注释
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('help_notes');
    }
}
