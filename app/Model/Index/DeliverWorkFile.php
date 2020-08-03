<?php
namespace App\Model\Index;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * 交付助手作品文件模型
 * @package App\Model\Index
 * @author jsyzchenchen@gmail.com
 * @date 2020/07/18
 */
class DeliverWorkFile extends Model
{
    use SoftDeletes;

    /**
     * 需要转换成日期的属性
     *
     * @var array
     */
    protected $dates = ['deleted_at'];
}
