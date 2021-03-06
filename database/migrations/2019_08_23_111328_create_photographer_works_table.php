<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreatePhotographerWorksTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('photographer_works', function (Blueprint $table) {
            $table->engine = 'innodb';
            $table->increments('id');
            $table->unsignedInteger('photographer_id')->index()->default(0)->comment('用户ID');
            $table->string('name', 50)->index()->default('')->comment('名称');
            $table->string('describe', 2000)->default('')->comment('描述');
            $table->unsignedTinyInteger('is_business')->default(0)->comment('是否商业【0:否;1:是】');
            $table->string('location', 100)->default('')->comment('地理位置名称');
            $table->string('address', 2000)->default('')->comment('详细地址');
            $table->string('latitude', 100)->default('')->comment('维度');
            $table->string('longitude', 100)->default('')->comment('经度');
            $table->string('customer_name', 50)->index()->default('')->comment('客户姓名');
            $table->unsignedInteger('photographer_work_customer_industry_id')->default(0)->comment('客户行业id');
            $table->unsignedInteger('project_amount')->default(0)->comment('项目金额，元');
            $table->unsignedTinyInteger('hide_project_amount')->default(0)->comment('项目金额保密【0:否;1:是】');
            $table->unsignedInteger('sheets_number')->default(0)->comment('成片张数');
            $table->unsignedTinyInteger('hide_sheets_number')->default(0)->comment('成片张数保密【0:否;1:是】');
            $table->unsignedInteger('shooting_duration')->default(0)->comment('拍摄时长，小时');
            $table->unsignedTinyInteger('hide_shooting_duration')->default(0)->comment('拍摄时长保密【0:否;1:是】');
            $table->unsignedInteger('photographer_work_category_id')->default(0)->comment('作品分类id');
            $table->unsignedSmallInteger('roof')->default(0)->comment('置顶');
            $table->unsignedSmallInteger('status')->default(0)->comment('状态【0:草稿;200:成功;400:删除;500:失败】');
            $table->string('xacode',1000)->default('')->comment('项目小程序码');
            $table->string('xacode_hyaline',1000)->default('')->comment('透明项目小程码');
            $table->timestamps();
        });
        DB::statement("ALTER TABLE `photographer_works` COMMENT '前台：用户项目'"); // 表注释
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('photographer_works');
    }
}
