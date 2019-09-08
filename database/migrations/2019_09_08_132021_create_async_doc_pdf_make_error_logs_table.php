<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateAsyncDocPdfMakeErrorLogsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create(
            'async_doc_pdf_make_error_logs',
            function (Blueprint $table) {
                $table->engine = 'innodb';
                $table->increments('id');
                $table->unsignedInteger('async_doc_pdf_make_id')->default(0)->comment('异步PDF生成ID');
                $table->text('error_info')->nullable()->comment('错误信息');
                $table->timestamps();
            }
        );
        DB::statement("ALTER TABLE `async_doc_pdf_make_error_logs` COMMENT '前台：异步PDF生成错误日志'"); // 表注释
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('async_doc_pdf_make_error_logs');
    }
}
