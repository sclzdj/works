<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateSystemDemosTable extends Migration {

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up() {
        Schema::create('system_demos', function (Blueprint $table) {
            $table->engine = 'innodb';
            $table->increments('id');
            $table->string('name')->unique()->default('')->comment('标识');
            $table->longText('value')->comment('值');
            //            $table->string('demo_text_1')->default('')->comment('输入框_1');
            //            $table->string('demo_text_2')->default('')->comment('输入框_2');
            //            $table->string('demo_textarea_1',1000)->default('')->comment('文本域_1');
            //            $table->string('demo_textarea_2',1000)->default('')->comment('文本域_2');
            //            $table->string('demo_radio_1')->default('')->comment('单选按钮_1');
            //            $table->string('demo_radio_2')->default('')->comment('单选按钮_2');
            //            $table->string('demo_checkbox_1')->default('')->comment('多选按钮_1');
            //            $table->string('demo_checkbox_2')->default('')->comment('多选按钮_2');
            //            $table->string('demo_select_1')->default('')->comment('下拉选择_1');
            //            $table->string('demo_select_2')->default('')->comment('下拉选择_2');
            //            $table->string('demo_select2_1')->default('')->comment('查找下拉选择_1');
            //            $table->string('demo_select2_2')->default('')->comment('查找下拉选择_2');
        }
        );
        DB::statement("ALTER TABLE `system_configs` COMMENT '后台:系统示例'"); // 表注释
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down() {
        Schema::dropIfExists('system_demos');
    }
}
