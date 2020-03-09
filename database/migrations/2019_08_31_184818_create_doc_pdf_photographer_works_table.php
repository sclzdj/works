<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateDocPdfPhotographerWorksTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('doc_pdf_photographer_works', function (Blueprint $table) {
            $table->engine = 'innodb';
            $table->increments('id');
            $table->unsignedInteger('doc_pdf_id')->index()->default(0)->comment('PDF文档ID');
            $table->unsignedInteger('photographer_work_id')->default(0)->comment('用户项目ID');
            $table->unsignedInteger('sort')->default(0)->comment('排序');
            $table->timestamps();
        });
        DB::statement("ALTER TABLE `doc_pdf_photographer_works` COMMENT '前台：PDF文档的用户项目'"); // 表注释
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('doc_pdf_photographer_works');
    }
}
