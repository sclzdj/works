<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateAsyncDocPdfMakesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create(
            'async_doc_pdf_makes',
            function (Blueprint $table) {
                $table->engine = 'innodb';
                $table->increments('id');
                $table->unsignedInteger('user_id')->index()->default(0)->comment('用户ID');
                $table->unsignedInteger('doc_pdf_id')->index()->default(0)->comment('pdfID');
                $table->unsignedSmallInteger('status')->default(0)->comment('状态【0:等待中;200:成功;400:删除;500:失败】');
                $table->timestamps();
            }
        );
        DB::statement("ALTER TABLE `async_doc_pdf_makes` COMMENT '前台：异步PDF生成'"); // 表注释
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('async_doc_pdf_makes');
    }
}
